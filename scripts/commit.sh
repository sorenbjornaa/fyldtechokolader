#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Brug: bash scripts/commit.sh "Commitbesked" [--all | [--patch FIL]... [-- FIL...]]

Uden filvalg bruges det, der allerede er staged.
--all          Vælg alle lokale ændringer.
--patch FIL    Vælg enkelte ændringer i en fil, som Git allerede følger.
-- FIL...      Vælg de angivne filer i deres helhed.
EOF
}

if [[ ${1:-} == --help || ${1:-} == -h ]]; then
  usage
  exit 0
fi
if [[ -z ${1:-} || ! $1 =~ [^[:space:]] ]]; then
  usage >&2
  exit 2
fi

message=$1
shift
stage_all=false
patch_paths=()
full_paths=()

while (($#)); do
  case $1 in
    --all)
      stage_all=true
      shift
      ;;
    --patch)
      if [[ -z ${2:-} || $2 == --* ]]; then
        usage >&2
        exit 2
      fi
      patch_paths+=("$2")
      shift 2
      ;;
    --)
      shift
      full_paths=("$@")
      break
      ;;
    *)
      usage >&2
      exit 2
      ;;
  esac
done

if $stage_all && [[ ${#patch_paths[@]} -gt 0 || ${#full_paths[@]} -gt 0 ]]; then
  printf '%s\n' 'Brug enten --all eller et specifikt filvalg.' >&2
  exit 2
fi

root=$(git -C "$(dirname "$0")" rev-parse --show-toplevel)
cd "$root"

if $stage_all; then
  git add -A
else
  if [[ ${#patch_paths[@]} -gt 0 ]]; then
    git add -p -- "${patch_paths[@]}"
  fi
  if [[ ${#full_paths[@]} -gt 0 ]]; then
    git add -- "${full_paths[@]}"
  fi
fi

if git diff --cached --quiet; then
  printf '%s\n' 'Ingen ændringer valgt til commit.'
  exit 0
fi

while IFS= read -r -d '' path; do
  case ${path##*/} in
    .env|.env.*|.envrc|wp-config.php|*.pem|*.key|*.p12|*.pfx|id_rsa|id_ed25519)
      printf 'Commit afbrudt. Fjern den følsomme fil fra staging: %s\n' "$path" >&2
      exit 1
      ;;
  esac
done < <(git diff --cached --name-only --diff-filter=ACMRT -z)

git diff --cached --check
staged_tree=$(git write-tree)
git --no-pager diff --cached --stat
printf '\nCommitbesked: %s\n' "$message"
while true; do
  read -r -p 'Opret lokalt commit? [j/N/v = vis diff] ' answer || exit 1
  case $answer in
    j|J|ja|Ja) break ;;
    v|V) git --no-pager diff --cached ;;
    *) printf '%s\n' 'Intet commit oprettet. Filvalget er stadig staged.'; exit 0 ;;
  esac
done

check_web=false
check_plugin=false
if ! git diff --cached --quiet -- apps/web; then check_web=true; fi
if ! git diff --cached --quiet -- wordpress/plugins/fyldtechokolader-core; then check_plugin=true; fi

if $check_web || $check_plugin; then
  snapshot=$(mktemp -d /tmp/fyldtechokolader-commit.XXXXXXXX)
  cleanup() {
    if [[ $snapshot == /tmp/fyldtechokolader-commit.???????? && -d $snapshot ]]; then
      rm -rf -- "$snapshot"
    fi
  }
  trap cleanup EXIT
  git archive "$staged_tree" | tar -x -C "$snapshot"

  if $check_web; then
    (
      cd "$snapshot/apps/web"
      npm ci --prefer-offline --no-audit --no-fund
      npm run lint
      npm run build
    )
  fi

  if $check_plugin; then
    (
      cd "$snapshot/wordpress/plugins/fyldtechokolader-core"
      composer test
      composer lint
    )
  fi
fi

if [[ $(git write-tree) != "$staged_tree" ]]; then
  printf '%s\n' 'Staging blev ændret efter godkendelsen. Kør kommandoen igen.' >&2
  exit 1
fi

git commit -m "$message"

if ! branch=$(git symbolic-ref --quiet --short HEAD); then
  printf '%s\n' 'Commit gemt lokalt. Vælg en Git-gren for at kunne pushe.'
  exit 0
fi

if ! git remote get-url --push origin >/dev/null 2>&1; then
  printf '%s\n' 'Commit gemt lokalt. Der er ingen Git-remote med navnet origin.'
  exit 0
fi

commit=$(git rev-parse HEAD)
printf '\nPush sender også eventuelle tidligere lokale commits på %s.\n' "$branch"
if [[ $branch == main ]]; then
  printf '%s\n' 'Push til main kan starte en produktionsudrulning på Vercel.'
fi

if read -r -p "Push til GitHub (origin/$branch)? [j/N] " answer; then
  case $answer in
    j|J|ja|Ja)
      if ! git push origin "$commit:refs/heads/$branch"; then
        printf '%s\n' 'Push mislykkedes. Committet er gemt lokalt.' >&2
        exit 1
      fi
      exit 0
      ;;
  esac
fi

printf '%s\n' 'Commit gemt lokalt.'
