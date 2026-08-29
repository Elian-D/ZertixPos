---
name: release
description: Use this skill when the user wants to cut a new release for this repo — phrases like "haz el release", "saquemos la version 1.2.0", "sube dev a main", "crea el tag", "publica el release", or /release. It drives the two-phase dev→main flow this project uses (a PR from dev into main, then once merged, tagging + a GitHub release) — always prefer it over improvising a generic tag/PR flow whenever the user is finishing a version, not just any feature branch.
---

# Release: `dev` → `main` con tag y GitHub Release

Este repo separa "terminar una feature" (skill `ship`, que va hacia su rama padre o `dev`) de "publicar una versión" (este skill, que va de `dev` hacia `main`). No los confundas — este solo corre cuando el usuario está cerrando una versión completa, no una rama individual.

Este skill asume un flujo de dos ramas (`dev`/`main`). Si el proyecto en el que se usa no tiene rama `dev` (solo `main`), no apliques este flujo tal cual — pregúntale al usuario cómo maneja sus releases ese repo en particular antes de improvisar.

## Primero: detectar en qué fase está el release

Compara `dev` y `main` antes de asumir nada:

```bash
git fetch origin
git log origin/main..origin/dev --oneline
```

- **Si hay commits ahí** (dev tiene cosas que main no tiene todavía) → estás en **Fase A**, hace falta el PR dev→main.
- **Si no hay ninguno** (main ya tiene todo lo de dev) → el PR ya se mergeó, estás en **Fase B**, hace falta el tag + release.

No le preguntes al usuario en qué fase está — averígualo con el comando de arriba y actúa según lo que encuentres.

## Fase A — Crear el PR de release (`dev` → `main`)

1. Si no tienes el número de versión, pregúntalo, o infierelo buscando un archivo `docs/features/vX.Y.Z.md` que coincida con el trabajo reciente.
2. Arma el cuerpo del PR resumiendo lo nuevo de esta versión — si existe `docs/features/vX.Y.Z.md`, sácalo de ahí (la sección de REQs completados es la fuente real); si no existe, resume a partir de los commits/PRs mergeados a `dev` desde el último tag (`git tag --sort=-creatordate | head -1` para saber cuál fue el último).
3. Muéstrale al usuario el título y el resumen antes de crear nada, espera confirmación.
4. `gh pr create --base main --head dev --title "..." --body "..."`.
5. Detente ahí. El usuario revisa y mergea en GitHub por su cuenta — igual que en `ship`, esa parte es suya.

## Fase B — Tag + GitHub Release (después de que el usuario ya mergeó)

1. Confirma la versión exacta con el usuario si no la tienes ya (formato `vX.Y.Z`).
2. Asegúrate de estar sobre `main` actualizado: `git checkout main && git pull origin main`.
3. Arma las notas del release — mismo criterio que el cuerpo del PR de Fase A: prioriza `docs/features/vX.Y.Z.md` si existe.
4. Muéstrale al usuario la versión exacta y las notas antes de tocar nada — crear un tag y publicar un release son acciones públicas y difíciles de deshacer limpiamente, así que esta confirmación no se salta nunca, aunque el usuario ya haya aprobado que este skill exista.
5. Una vez confirmado:
   ```bash
   git tag -a vX.Y.Z -m "Descripción corta de la versión"
   git push origin vX.Y.Z
   gh release create vX.Y.Z --title "vX.Y.Z" --notes "..."
   ```

## Qué no hacer

- No mezclar este flujo con el de `ship` — si el usuario dice "sube esto a dev", es `ship`, no `release`.
- No asumir la fase sin comparar `dev`/`main` primero — preguntarle al usuario "¿ya mergeaste el PR?" en vez de verificarlo con git es más lento y menos confiable que simplemente mirar el estado real.
- No tagear ni publicar el release sin confirmación explícita de la versión y las notas, incluso si el resto del flujo ya se aprobó.
