---
name: ship
description: Use this skill whenever the user wants to commit their current work in this repo and open a pull request into its parent branch — phrases like "haz el commit", "sube esto", "commitea esto", "crea el PR", "manda esto a la rama padre", or "ahora haz el PR hacia dev" when closing out a release branch, or /ship. Always prefer this over an ad-hoc commit when the user is wrapping up a chunk of work on a feature/fix/refactor/release branch, even if they don't name the skill explicitly — any request to turn working code into a committed, pushed, reviewable PR should trigger this. The target branch is NOT always `dev` — this repo nests branches (feature/vX.Y.Z-* → release/vX.Y.Z → dev), so this skill must figure out the real parent each time, not assume.
---

# Ship: commit + PR hacia la rama padre correcta

Este skill existe porque el usuario es un dev solo que piensa en lógica y me delega la implementación — ya probó y validó su trabajo en el momento, antes de pedir el commit. Por eso el PR que produce este skill **no lleva checklist de pruebas**: sería ruido para su flujo, no valor.

## Paso 1 — Leer el cambio real, no adivinar

Corre `git status` y `git diff` (staged + unstaged) sobre todo lo modificado. No decidas nada todavía por cantidad de archivos ni tamaño del diff — lee qué cambió realmente.

## Paso 2 — Decidir tipo y separación por RAZÓN, no por tamaño

La pregunta que importa es "¿por qué se hizo este cambio?", no "cuántos archivos toca". Sigue este árbol de decisión:

1. ¿El sistema puede hacer algo **nuevo** que antes no podía? → `feat`
2. ¿Cambia la estructura/implementación de algo existente **sin cambiar su objetivo**? → `refactor`
3. ¿Corrige un error o bug? → `fix`
4. ¿Solo afecta UI/vistas/estilos? → `ui`
5. ¿Es limpieza menor (código muerto, imports, etc.)? → `chore`
6. ¿Es documentación? → `docs`

**Regla dura: un commit = una sola razón.** Si el diff mezcla responsabilidades independientes (ej. una feature nueva + un fix de un bug no relacionado), no los metas en el mismo commit — sepáralos con `git add` selectivo, uno por cada razón, y dile al usuario explícitamente qué mezclaste y por qué lo separaste. No te calles esto: es exactamente el tipo de aviso que el usuario quiere recibir sin que se lo tenga que pedir.

## Paso 3 — Redactar los mensajes en español, igual que el historial real del repo

Corre `git log --oneline -15` si necesitas recordar el estilo exacto — ya está establecido en este repo, no lo inventes de cero. Ejemplos reales ya en el historial:

```
feat(tax): adaptar modelos, servicios y requests al nuevo esquema de impuestos
ui(tax): actualizar interfaz de POS, cotizaciones, productos e impresiones con desglose de impuestos
docs(tax): agregar analisis de politica de descuentos y actualizar tests de checkout
```

Formato: `tipo(alcance): descripción breve en español, minúscula después de los dos puntos, sin punto final`. El alcance es el módulo real afectado (`tax`, `pos`, `collections`, `roadmap`, etc.), no el nombre del archivo.

## Paso 4 — Mostrar el plan antes de tocar nada

Antes de hacer `git add`/`git commit`, muéstrale al usuario la lista de commits que vas a crear (mensaje + archivos de cada uno) y espera confirmación. Esto no es opcional — aunque el usuario ya aprobó que este skill exista, cada commit real todavía necesita su visto bueno porque estás modificando el historial del repo.

## Paso 5 — Commitear, y confirmar antes de `push`

Una vez confirmado el plan, crea los commits. Antes de `git push`, pide confirmación explícita otra vez — es una acción visible hacia el remoto y hacia cualquier colaborador, así que nunca se salta este paso aunque el usuario haya aprobado el skill de antemano.

## Paso 6 — Averiguar la rama padre correcta (nunca asumas `dev` de entrada, y el anidamiento puede ser más profundo de 2 niveles)

Este repo anida ramas, y no siempre a la misma profundidad. Lo típico es `feature/vX.Y.Z-*` → `release/vX.Y.Z` → `dev`, pero una fase grande puede partirse en sub-ramas propias (ej. `feature/v1.2.0-brand-tokens-buttons` → `feature/v1.2.0-brand-tokens` → `release/v1.2.0` → `dev`). No asumas un número fijo de niveles — busca la rama padre real cada vez, así:

1. **El usuario lo dijo explícito** (ej. "ahora haz el PR hacia dev", "manda esto a brand-tokens") → usa exactamente eso, sin cuestionarlo.
2. **Si no lo dijo, infiere por coincidencia de nombre, de más específico a menos específico — no por un patrón fijo de 2 niveles:**
   - Lista las ramas reales del repo: `git branch -a`.
   - Toma el nombre de la rama actual y quítale el prefijo de tipo (`feature/`, `fix/`, `refactor/`, `ui/`, `release/`, etc.) — te queda el "slug" (ej. de `feature/v1.2.0-brand-tokens-buttons` el slug es `v1.2.0-brand-tokens-buttons`).
   - Busca, entre todas las demás ramas del repo (con cualquier prefijo de tipo), la que tenga el slug más largo que sea prefijo exacto del tuyo, cortando en un límite de guión (ej. `v1.2.0-brand-tokens` es un prefijo válido de `v1.2.0-brand-tokens-buttons`; `v1.2.0-brand` no lo sería porque corta a mitad de palabra). Esa rama es tu padre directo, sin importar si su propio prefijo de tipo es distinto al tuyo (una `feature/*` puede perfectamente anidar bajo una `release/*`, o una `ui/*` bajo una `feature/*`).
   - Si ninguna rama matchea de esa forma, tu slug no tiene padre anidado — cae al caso base: `dev` si existe, o la rama principal real del repo si no.
3. **Si hay ambigüedad real** (dos ramas distintas matchean con el mismo largo, o la rama que infieres como padre no existe en el remoto) → pregúntale al usuario directamente cuál es la rama padre en vez de adivinar — equivocar el destino de un PR es más caro de corregir que una pregunta.

Dile al usuario qué rama detectaste como destino y por qué (qué coincidencia encontraste), antes de crear el PR — así puede corregirte en el momento si te equivocaste de rama padre.

Usa `gh pr create --base <rama-destino>`. El título va en español, corto, estilo conventional-commit. El cuerpo usa **siempre estas 4 secciones, en este orden, nunca menos**:

```markdown
## Descripción
## Problema
## Solución
## Resultado
```

Para cambios medianos/grandes, puedes sumar (nunca en vez de las 4 base):

```markdown
### Cambios técnicos relevantes
### Impacto en otros módulos
### Notas para revisión
```

**No agregues ninguna sección de plan de pruebas/checklist de QA** — el usuario prueba en vivo antes de llegar a este paso, y una lista de "cosas por verificar" ahí sería redundante con lo que él ya hizo.

Muéstrale el título y cuerpo del PR antes de crearlo, espera confirmación, y créalo con `gh pr create`.

## Paso 7 — No mergees

Este skill crea el PR y se detiene ahí. El usuario revisa y mergea desde GitHub por su cuenta — eso es explícitamente su parte del flujo, no la tuya.
