#!/bin/bash

if [ $# -lt 2 ]; then
  echo "format: git_cleanup <repo name> [-x/--expunge <directory 1> -l/--keeplatest <directory 2> ...]"
  exit
fi

PROJ="$1"
shift

export EXPUNGE=()
export KEEPLATEST=()

while [[ $# > 1 ]]; do

  key="$1"
  shift

  case $key in
    -x|--expunge)
    folder="$1"
    EXPUNGE+=("$folder")
    echo "expunging: $folder"
    shift
    ;;
    -l|-k|--keeplatest)
    folder="$1"
    KEEPLATEST+=("$folder")
    echo "keeping latest: $folder"
    shift
    ;;
    *)
    echo "unknown option $key"
    echo "format: git_cleanup <repo name> [-x/--expunge <directory 1> -l/--keeplatest <directory 2> ...]"
    exit
    ;;
  esac
done

echo -------------------------------------------------------------------

echo setting up working repositories
cd ~/repos

# folders used:
#   X-shrinking: (non-bare) working repo where we rewrite git indexes to cut the unwanted folders
#   X-stash: For retainlatest folders, keep a copy here during the expunging
#   X-addback: a (non-bare) clone of the post-expunged shrinking repo, where we add back stashed folders
#   X-shrink.git: The output

[ -d ${PROJ}-shrinking ] && rm -rf ${PROJ}-shrinking
[ -d ${PROJ}-stash ] && rm -rf ${PROJ}-stash
[ -d ${PROJ}-shrunk.git ] && rm -rf ${PROJ}-shrunk.git

mkdir ${PROJ}-stash

git clone --no-local ${PROJ}.git ${PROJ}-shrinking
cd ${PROJ}-shrinking


for folder in ${KEEPLATEST[@]}; do
  echo "stashing the latest version of $folder"
	rsync -az ~/repos/${PROJ}-shrinking/$folder/ ~/repos/${PROJ}-stash/$folder
done

echo -------------------------------------------------------------------

echo filtering...

BADFOLDERS=("${EXPUNGE[@]}" "${KEEPLATEST[@]}")

FILTERPARTS=$(for folder in ${BADFOLDERS[@]}; do echo "git rm -rf --cached --ignore-unmatch $folder"; done)

export FILTERCMD=${FILTERPARTS[0]}$(printf " \&\& %s" "${FILTERPARTS[@]:1}")

git filter-branch -f --index-filter '$FILTERCMD' --prune-empty --tag-name-filter cat -- --all
rm -Rf refs/original
rm -Rf refs/logs
git gc


echo -------------------------------------------------------------------

cd ~/repos
git clone --no-local ${PROJ}-shrinking ${PROJ}-addback
cd ${PROJ}-addback

for folder in ${KEEPLATEST[@]}; do
  echo "restoring from stashed copy of $folder"
	rsync -az ~/repos/${PROJ}-stash/$folder/ ~/repos/${PROJ}-addback/$folder
	git add . --all
	git commit -a -m "re-adding latest version of $folder"
done


echo "Cloning to output repository"
cd ~/repos
git clone --bare --no-local ${PROJ}-addback ${PROJ}-shrunk.git

read -p "delete working data (y/n)?"
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
  rm -rf ${PROJ}-shrinking
  rm -rf ${PROJ}-addback
  rm -rf ${PROJ}-stash
fi

read -p "backup original and replace with shrunk repository (y/n)?"
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
  mv ${PROJ}.git ${PROJ}-$(date -u +"%Y-%m-%dT%H:%M:%SZ").git
  mv ${PROJ}-shrunk.git ${PROJ}.git
fi



