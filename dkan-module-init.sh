set -e

# Name of the current module.
DKAN_MODULE="dkan_harvest"

# DKAN branch to use
DKAN_BRANCH="harvest_dkan_integration"

COMPOSER_PATH=".composer/vendor/bin"

#Fix for probo not setting the composer path.
if [[ "$PATH" != *"$COMPOSER_PATH"* ]]; then
  echo "> Composer PATH is not set. Adding temporarily.. (you should add to your .bashrc)"
  echo "PATH (prior) = $PATH"
  export PATH="$PATH:~/$COMPOSER_PATH"
fi

wget -O /tmp/dkan-init.sh https://raw.githubusercontent.com/NuCivic/dkan/$DKAN_BRANCH/dkan-init.sh

# Make sure the download was at least successful.
if [ $? -ne 0 ] ; then
  echo ""
  echo "[Error] Failed to download the dkan-init.sh script from github dkan. Branch: $DKAN_BRANCH . Perhaps someone deleted the branch?"
  echo ""
  exit 1
fi

#Only stop on errors starting now..
set -e

# OK, run the script.
bash /tmp/dkan-init.sh $DKAN_MODULE $@ --skip-reinstall --branch=$DKAN_BRANCH

ahoy dkan module-link $DKAN_MODULE
ahoy dkan module-make $DKAN_MODULE
ahoy dkan reinstall
ahoy drush en migrate dkan_migrate_base $DKAN_MODULE -y

echo ""
echo "-------> Run harvester:"
echo ""
ahoy drush cc all
ahoy drush dkan-hr
