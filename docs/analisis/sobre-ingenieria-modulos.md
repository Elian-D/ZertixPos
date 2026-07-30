# Análisis: Sobre-ingeniería en módulos del sistema

**Fecha:** 2026-07-11
**Contexto:** El sistema se originó como un ERP a medida para una empresa de venta de hielo (manejo de freezers en el punto de venta del cliente, distribución con rutas/almacenes móviles). Se está evaluando qué tan reutilizable es para negocios pequeños/medianos (colmados, cafeterías, ferreterías) que solo necesitan facturación + POS, sin toda la carga operativa del caso original.

**Prioridad actual:** terminar el módulo POS. Este documento es un mapa de deuda de alcance para atacar **después**, no antes.

---

## 1. Módulo Accounting (Contabilidad) — análisis profundo

**Actualizado:** 2026-07-29. Reemplaza y profundiza la sección original del 2026-07-11 (evaluación por perfil de cliente + veredicto), que se queda corta: decía "está bien implementado técnicamente, solo sobra por defecto". Tras leer el motor completo (`JournalEntryService`, `ReceivableService`, `PaymentService`, `AccountingDashboardController`, el listener POS, y el seeder del plan de cuentas) la conclusión cambia: **el problema no es solo de exposición, es también de diseño** — hay una capa entera de código frágil que ni siquiera un contable real querría usar tal cual está.

### Tamaño
110 archivos (solo contando path con "accounting"): 60 en `app/`, 36 vistas Blade, 8 entre migraciones/seeders/factories, 6 de rutas (`routes/admin/accounting/*.php`, 5 archivos).

### Submódulos
- **Plan de cuentas** (`AccountingAccount`): árbol jerárquico código/padre-hijo, tipos (Activo/Pasivo/Patrimonio/Ingreso/Costo/Gasto), niveles.
- **Asientos contables** (`JournalEntry`/`JournalItem`): partida doble real — crear, editar (solo draft), postear, cancelar, **reversar** (`JournalEntryService::reverse()`).
- **Cuentas por Cobrar** (`Receivable`): balance y status (paid/partial/unpaid/cancelled) por cliente, con `is_overdue` calculado.
- **Pagos** (`Payment`): documento contable formal con correlativo fiscal (`PAG`), genera su propio asiento, ciclo de vida de anulación. **Distinto y paralelo** a `SalePayment` (pago operativo de una venta/POS, sin asiento, sin numeración) — dos conceptos de "pago" que no se tocan entre sí.
- **Tipos de documento** (`DocumentType`): correlativos por tipo (FAC, REC, NC, MAN, PAG).
- **Dashboard financiero** (`AccountingDashboardController`, 297 líneas): ratio de liquidez, margen bruto, flujo de caja, alertas financieras automáticas ("Cartera Sobre-extendida", "Exceso de Liquidez"), todo derivado de sumar/restar `JournalItem` filtrando por prefijo de código de cuenta.

### (a) ¿Ayuda o perjudica según el perfil del usuario?

**Perfil "tengo un contable / quiero llevar contabilidad formal"** — el perfil para el que el módulo dice estar hecho. Aquí es donde más sorprende: **tampoco lo ayuda**, y por razones que van más allá del alcance:

- **El plan de cuentas está hardcodeado en el código, no solo en el seeder.** `SaleService::generateSaleAccountingEntry()` busca `AccountingAccount::where('code','4.1')->firstOrFail()` y `'1.1.02'` ([SaleService.php:205](app/Services/Sales/SalesServices/SaleService.php:205), [SaleService.php:214](app/Services/Sales/SalesServices/SaleService.php:214)); `ReceivableService` busca `'1.1.02'` ([ReceivableService.php:84](app/Services/Accounting/Receivable/ReceivableService.php:84)); `PaymentService` busca `'1.1.01'` ([PaymentService.php:115](app/Services/Accounting/Payment/PaymentService.php:115)); el listener POS busca `'1.1.01'` ([CreateAccountingEntryForMovement.php:28](app/Listeners/Sales/Pos/CreateAccountingEntryForMovement.php:28)); y **todo** `AccountingDashboardController` (liquidez, margen, flujo de caja, gráficos) filtra por `LIKE '1.1.01%'`, `'4%'`, `'5%'`, `'5.1%'`, `'2.1'`, etc. Un contable real que edite o reorganice el plan de cuentas —cosa básica que cualquier contable hace al adaptar el sistema a su empresa— **rompe silenciosamente** ventas, cobros, el dashboard y el listener de caja, porque `firstOrFail()` avienta una excepción a mitad de una venta si el código no existe, o el dashboard simplemente empieza a mostrar ceros si el código cambió de sentido. El "plan de cuentas editable" que ve el usuario en la UI es una ilusión: es *decorativo* para casi todo el sistema real.
- **El seeder del plan de cuentas es el del negocio de hielo original**, no un plan de cuentas genérico ni configurable por tipo de negocio: `5.2 Costos de Producción/Transformación (para las entradas de hielo fabricado)`, `5.3.02 Suministros (Fundas, insumos)` ([AccountingAccountSeeder.php:46](database/seeders/AccountingSeeders/AccountingAccountSeeder.php:46)). Un contable dominicano estándar espera algo más cercano al catálogo de cuentas típico DGII/NIIF-PYMES, no una nomenclatura de fábrica de hielo.
- **No hay motor de asientos automáticos configurable.** Cada integración (venta, cobro, movimiento de caja) tiene su propia lógica de asiento *hardcodeada en PHP* dentro del service correspondiente (`SaleService::generateSaleAccountingEntry`, `PaymentService::createPayment`, el listener POS). Un contable no puede ir a una pantalla de "reglas de asiento automático" y decir "las ventas van a la cuenta X en vez de 4.1" — tendría que pedirle a un desarrollador que cambie código PHP. Eso no es un sistema contable configurable, es contabilidad *simulada por el programador*, no por el contable.
- **El listener de asientos de movimientos de caja POS (`CreateAccountingEntryForMovement`) nunca está registrado** en `EventServiceProvider` — código muerto que aparenta funcionar (existe, tiene tests conceptuales, se ve completo) pero jamás corre. Un contable que audite el libro diario y no vea reflejados los movimientos de caja del POS no tendría cómo saber por qué, porque en el código *parece* que sí se generan.
- **Falta lo mínimo que un contable pediría de un ERP:** no hay Balance General ni Estado de Resultados como reporte formal (solo el dashboard con tarjetas/gráficos, no un reporte exportable con formato contable), no hay libro mayor por cuenta navegable fácilmente, no hay cierre de período/año fiscal, no hay manejo de impuestos por pagar retenido en las cuentas (la cuenta `2.1.03 Impuestos por Pagar` está comentada/deshabilitada en el propio seeder, [AccountingAccountSeeder.php:27](database/seeders/AccountingSeeders/AccountingAccountSeeder.php:27)). Es decir: tiene la fachada de partida doble (que sí está bien hecha técnicamente: valida que cuadre, transacciones atómicas, estados draft/posted/cancelled) pero le faltan los entregables que un contable realmente necesita entregar (balance, P&L, cierre).

**Conclusión (a) — perfil contable:** el módulo **no ayuda a un contable real**, lo estorbaría. No porque le "sobre" funcionalidad, sino porque la funcionalidad que tiene está acoplada por código a una convención de cuentas específica y no expone las palancas de configuración (reglas de asiento, reportes formales) que un contable necesita para poder trabajar sin depender de un programador cada vez.

**Perfil "no sé de contabilidad, quiero manejar lo básico"** (el más común, según el análisis previo del 2026-07-11): el módulo lo perjudica activamente, por exposición y por vocabulario:

- Le aparecen en el menú "Plan de Cuentas", "Asientos Contables", "Cuentas por Cobrar" con partida doble, sin ningún significado operativo para alguien que solo quiere saber "¿gané o perdí este mes?".
- El dashboard (`accounting.dashboard`) le muestra "Ratio de Liquidez", "Margen Bruto", alertas como "Cartera Sobre-extendida: sus cuentas por cobrar triplican sus deudas" — lenguaje financiero que un dueño de colmado no puede accionar ni interpretar sin ayuda externa, cuando lo único que probablemente necesita es "esto entró, esto salió, esto me deben".
- La `Receivable` (CxC) ya se genera automáticamente por cada venta a crédito vía `SaleService` — eso sí es útil y transparente para este perfil (no tiene que crear un asiento a mano). El problema es que todo lo demás del módulo (plan de cuentas, asientos manuales, pagos con correlativo `PAG`) se le expone igual, sin necesidad.

### (b) Qué necesitaría REALMENTE alguien que quiere llevar contabilidad (o tiene un contable externo)

La pregunta correcta no es "¿cómo hacemos más grande/mejor el sistema de partida doble?" — es "¿qué necesita el dueño del negocio, y qué necesita el contable que probablemente ya usa su *propio* software (o Excel) por fuera?". En la gran mayoría de negocios pequeños/medianos en RD, el contable **no vive dentro del ERP del cliente**: exporta datos, o pide reportes puntuales. El sistema no tiene que *ser* la contabilidad, tiene que *alimentarla*.

Lo que realmente hace falta, en orden de valor real:

1. **Un reporte de Ingresos vs. Gastos con margen, derivado de Ventas + Inventario + gastos operativos simples**, sin necesidad de que el usuario entienda "débito/crédito". Esto ya casi existe como dato (ventas están en `Sale`, costo en `InventoryMovement`/`Product`), solo falta la vista que lo presente en términos de negocio, no de libro contable.
2. **Cuentas por Cobrar y por Pagar simples**: quién me debe, cuánto, desde cuándo — esto **ya lo tiene** (`Receivable`) y está bien, es lo más reutilizable del módulo tal cual está. Falta el espejo: Cuentas por Pagar a proveedores (hoy solo existe la cuenta contable `2.1.01` pero no hay entidad `Payable` ni flujo — es una laguna real, no sobra-ingeniería).
3. **Un export limpio para el contable externo**: movimientos del período (ventas, compras, gastos, cobros, pagos) en un formato que un contable pueda importar a su propio sistema (Excel/CSV con las columnas que un contable pide: fecha, documento, monto, tipo, cliente/proveedor) — no un asiento de partida doble, un *libro de operaciones*.
4. **Gastos operativos simples**: una pantalla de "Gasto" (concepto, monto, fecha, categoría opcional) sin necesitar saber a qué cuenta contable va — el sistema decide la cuenta por detrás si aplica el modo avanzado, o ni siquiera la usa si está en modo simple.
5. **Solo para el que explícitamente pide contabilidad formal (modo avanzado, opt-in)**: ahí sí tiene sentido plan de cuentas editable *de verdad* (que otros módulos no dependan de códigos hardcodeados), asientos manuales, reportes formales (Balance General, Estado de Resultados navegable, no solo dashboard) y cierre de período. Ese modo avanzado debería ser una capa aparte, no la funcionalidad exigida a todos por defecto — que es exactamente lo que dice el análisis original, y sigue siendo cierto para el *alcance*, pero además esa capa avanzada, tal como existe hoy, **necesitaría rehacerse desacoplada del código** para servirle de verdad a un contable, no solo esconderse.

**Resumen de (b):** el sistema no necesita "ser un sistema de contabilidad" — necesita ser un sistema de ventas/inventario/POS que **produce datos limpios y exportables**, con una capa opcional de partida doble para el puñado de clientes que la piden explícitamente, y esa capa opcional debe ser configurable sin tocar código (el plan de cuentas no puede seguir siendo un string hardcodeado en 6 archivos distintos).

### (c) Comparación con el análisis del 2026-07-11 y qué cambia

El análisis anterior acertó en el diagnóstico de **alcance** (modo simple/avanzado, ocultar por defecto) y en el **hilo común** (Accounting se autoinyecta en Clients/Inventory/Sales). Ambos siguen siendo válidos y se mantienen en la sección 2 de este documento. Lo que este análisis más profundo agrega y corrige:

- No es solo "sobra por defecto" — el motor mismo **no es reutilizable ni seguro para un contable real** por el acoplamiento a códigos de cuenta hardcodeados (6+ puntos de acoplamiento distintos: `SaleService` x2, `ReceivableService`, `PaymentService`, el listener POS, y prácticamente todo `AccountingDashboardController`).
- El plan de cuentas seed no es neutro/genérico, es el del negocio de hielo — otro rastro del origen del sistema (coherente con el hallazgo de la sección 2 sobre `Client`/`Equipment`).
- Falta contraparte de Cuentas por Pagar (`Payable`) — hoy solo Cuentas por Cobrar tiene entidad propia.
- El listener muerto de asientos POS sigue confirmado (ver hallazgo original).

### Veredicto

Sobre-ingeniería de alcance **y** de diseño. La partida doble en sí (`JournalEntryService`) está bien hecha en aislamiento — valida balance, usa transacciones, maneja draft/posted/cancelled/reverse correctamente. El problema es todo lo que cuelga alrededor: integraciones que hardcodean códigos de cuenta en vez de resolverlos por configuración, un plan de cuentas seed específico del negocio original, y ausencia de los entregables que un contable de verdad necesita (reportes formales, cierre, CxP). Está diseñado para *aparentar* contabilidad formal más que para *servir* a quien la ejerce.

### Roadmap de limpieza / refactor (Accounting)

Orden pensado para no romper nada mientras se hace, y para que cada paso deje el sistema en un estado funcional:

1. **Romper el acoplamiento a códigos hardcodeados** (antes que nada — es lo que hace peligroso tocar el plan de cuentas hoy). Introducir un mapeo configurable (tabla de "roles de cuenta": `cash_default`, `receivable_default`, `sales_revenue`, etc., o campos dedicados como ya existe `PosTerminal->cash_account_id`) y reemplazar los `AccountingAccount::where('code', '...')->firstOrFail()` en `SaleService`, `ReceivableService`, `PaymentService`, el listener POS y `AccountingDashboardController` por lecturas a ese mapeo. Sin este paso, cualquier otro cambio al plan de cuentas sigue siendo peligroso.
2. **Definir el flag "modo contable"** (simple/avanzado) a nivel de `ConfiguracionGeneral`, igual patrón que `usa_ncf`.
   - Modo simple (default): oculta plan de cuentas, asientos manuales, pagos con correlativo formal. Muestra solo CxC (ya existe, se mantiene) + un reporte nuevo de Ingresos/Gastos con margen.
   - Modo avanzado (opt-in, permiso dedicado): expone todo lo actual, ya desacoplado gracias al paso 1.
3. **Construir el reporte simple de Ingresos/Gastos + margen** alimentado por `Sale`/`InventoryMovement`, sin vocabulario contable, para el perfil "no sé de contabilidad" — este es el entregable de mayor valor para el usuario más común.
4. **Agregar una pantalla de "Gasto" simple** (concepto, monto, fecha, categoría opcional) que en modo avanzado sí genere su asiento por detrás (usando el mapeo del paso 1) y en modo simple solo alimente el reporte del paso 3.
5. **Decidir el plan de cuentas seed**: o se deja como está pero documentado como "ejemplo editable", o se reemplaza por un catálogo neutro más cercano al estándar RD/NIIF-PYMES — decisión de producto, no técnica, pero bloqueada hasta que el paso 1 haga seguro editarlo.
6. **Cuentas por Pagar (`Payable`)** como espejo de `Receivable`, solo si el modo avanzado lo justifica (o como reporte simple de "lo que debo a proveedores" en modo simple, sin asiento).
7. **Reportes formales** (Balance General, Estado de Resultados navegable, export limpio para contador externo) — exclusivo de modo avanzado, es lo que realmente le serviría a un contable que sí decide usar el sistema.
8. **Registrar o eliminar el listener muerto de asientos POS** (decisión consciente) — condicionarlo al modo avanzado si se registra.

---

## 2. Clients, Sales, Inventory

### Clients — el más sobre-diseñado para negocio pequeño
`Client` → `PointOfSale` → `Equipment` (+ `EquipmentType`, `BusinessType`) modela **distribución con activos en campo**: cada cliente puede tener varios puntos de venta, y cada punto de venta tiene equipos (freezers) con serial y código autogenerado. Esto es exactamente el caso de uso original (hielo + freezers prestados en cada colmado/farmacia). Un negocio pequeño típico no tiene "puntos de venta del cliente" ni "equipos" que rastrear — su `Client` debería ser plano: nombre, contacto, tax_id, crédito.

**Acción futura:** extraer `PointOfSale`/`Equipment`/`BusinessType`/`EquipmentType` del núcleo de `Clients` a un submódulo opcional (tipo "activos en campo" / "field assets"), activable por config/permiso. `Client` core no debería cargar esas relaciones por defecto.

### Sales — núcleo razonable, acoplamientos opcionales ya bien encaminados
`Sale`/`SaleItem`/`Invoice`/`Quote` sirve para cualquier tamaño de negocio. Trae de fábrica sesión/terminal POS, NCF fiscal (obligatorio solo en RD) y multipay. El NCF ya está bien aislado: namespace `Sales/Ncf/` separado y gateado por `general_config()->usa_ncf` vía `Sale::requiresNcf()` — este es el patrón correcto a replicar en otros lados.

**Acción futura:** aplicar el mismo patrón de gate por config a la sesión/terminal POS, para negocios que solo facturan por mostrador sin apertura/cierre formal de caja. No es urgente.

### Inventory — liviano en sí mismo, pero filtra complejidad de Accounting
`Warehouse`, `InventoryStock`, `InventoryMovement` son simples y correctos para cualquier tamaño. El problema: `Warehouse::booted()` **crea automáticamente una subcuenta contable** (bajo `1.1.03`) cada vez que se crea un almacén — mismo patrón de Accounting imponiéndose donde no se pidió.

**Acción futura:** sacar `createAccountingAccount()` del `booted()` de `Warehouse` y moverlo a un listener opcional, activo solo si el "modo contable avanzado" está encendido. `accounting_account_id` ya es nullable, no rompe nada quitarlo del flujo automático.

### Resumen priorizado

| Módulo | Nivel de sobre-ingeniería para negocio pequeño | Acción |
|---|---|---|
| Clients (Equipment/PointOfSale) | Alto — lógica específica del caso hielo, no genérica | Extraer a submódulo opt-in |
| Accounting | Alto — expuesto por defecto a todos | Modo simple / avanzado |
| Inventory → Warehouse → Accounting | Medio — acoplamiento puntual | Quitar auto-creación de cuenta del `booted()` |
| Sales | Bajo — núcleo sano, NCF ya bien gateado | Replicar el gate a POS (opcional) |

### Hilo común
En los tres casos, **Accounting se autoinyecta**: en Clients (`accounting_account_id`), en Inventory (`Warehouse::createAccountingAccount`), en Sales (asientos automáticos por venta/pago). Desacoplar Accounting detrás de un flag "modo contable avanzado" resuelve la mayor parte del problema transversal sin tocar Sales, Inventory ni Products, que ya están a un nivel razonable para negocio pequeño.

---

## Orden sugerido de trabajo futuro (después de terminar el POS)

1. Terminar y estabilizar el POS (prioridad actual, no tocar nada de lo de arriba todavía).
2. Romper el acoplamiento a códigos de cuenta hardcodeados en `SaleService`, `ReceivableService`, `PaymentService`, el listener POS y `AccountingDashboardController` (ver roadmap detallado en la sección 1, paso 1) — es el prerrequisito de todo lo demás en Accounting.
3. Definir el flag/config de "modo contable" (simple vs avanzado) y qué ve cada uno por defecto.
4. Construir el reporte simple de Ingresos/Gastos + margen para el perfil no-contable (mayor valor, menor riesgo).
5. Desacoplar la auto-creación de cuentas contables en `Warehouse` detrás de ese flag.
6. Extraer `PointOfSale`/`Equipment` de `Clients` a submódulo opcional.
7. Decidir qué hacer con el listener POS→Accounting muerto (registrarlo condicionado al modo avanzado, o eliminarlo si no aporta).
8. Evaluar Cuentas por Pagar (`Payable`) y reportes formales (Balance General, Estado de Resultados, export a contador externo) — exclusivos del modo avanzado, una vez el paso 2 lo haga seguro.
