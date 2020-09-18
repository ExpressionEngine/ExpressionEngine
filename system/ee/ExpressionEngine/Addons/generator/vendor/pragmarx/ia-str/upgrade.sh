#!/usr/bin/env bash

##
 # Include dotfiles on file operations
 #
shopt -s dotglob

##
 # App
 #
function main()
{
    showAppTitle

    prepareEnvironment $1

    displayVariables

    cleanDirectories

    downloadRepository

    extractZip

    copyClasses

    copyContracts

    copyTraits

    copyStubs

    downloadTests

    renameNamespace

    fillAliases

    removeDownloadedRepositoryDir

    runTests
}

##
 # Prepare the environment
 #
function prepareEnvironment()
{
    title "Preparing environment variables..."

    ##
     # Define all variables
     #
    requestedVersion=$1
    rootDir=.
    baseDir=${rootDir}/src
    vendor=laravel
    project=framework
    oldNamespace='Illuminate'
    newNamespace='IlluminateAgnostic\\Str'
    oldVanillaNamespace='Vanilla'
    newVanillaNamespace='Illuminate'
    repository=https://github.com/$vendor/$project.git

    getCurrentVersionFromGitHub

    repositoryDir=${rootDir}/$project-${collectionVersion}
    repositorySrcDir=${repositoryDir}/src
    collectionZip=${rootDir}/$project-${collectionVersion}.zip
    collectionZipUrl=https://github.com/$vendor/$project/archive/v${collectionVersion}.zip
    oldNamespaceDir=${repositorySrcDir}/${oldNamespace}
    newNamespaceDir=${baseDir}
    testsDir=${rootDir}/tests
    testsBaseUrl=https://raw.githubusercontent.com/${vendor}/${project}/v${collectionVersion}/tests
    stubsDir=${rootDir}/stubs
    aliasFile=${baseDir}/Support/alias.php
carriageReturn="
"

    classes=(
        'Support/Arr'
        'Support/Carbon'
        'Support/Collection'
        'Support/Debug/Dumper'
        'Support/Debug/HtmlDumper'
        'Support/HigherOrderCollectionProxy'
        'Support/HigherOrderTapProxy'
        'Support/HtmlString'
        'Support/Optional'
        'Support/Pluralizer'
        'Support/Str'
        'Support/Enumerable'
        'Support/LazyCollection'
    )

    traits=(
        'Support/Traits/Macroable.php'
        'Support/Traits/EnumeratesValues.php'
    )

    contracts=(
        'Contracts/Support/Arrayable.php'
        'Contracts/Support/Jsonable.php'
        'Contracts/Support/Htmlable.php'
    )

    tests=(
        'Support/SupportArrTest.php'
        'Support/SupportCarbonTest.php'
        'Support/SupportCollectionTest.php'
        'Support/SupportMacroableTest.php'
        'Support/SupportStrTest.php'
    )

    stubs=(
        'src/Support/alias.php'
        'src/Support/helpers.php'
        'tests/bootstrap.php'
        'tests/StrTest.php'
    )
}

##
 # Display all variables
 #
function displayVariables()
{
    title "Displaying variables"
    echo baseDir = ${baseDir}
    echo collectionVersion = ${collectionVersion}
    echo repositoryDir = ${repositoryDir}
    echo repositorySrcDir = ${repositorySrcDir}
    echo collectionZip = ${collectionZip}
    echo baseDir = ${baseDir}
    echo oldNamespace = ${oldNamespace}
    echo newNamespace = ${newNamespace}
    echo oldNamespaceDir = ${oldNamespaceDir}
    echo newNamespaceDir = ${newNamespaceDir}
    echo testsDir = ${testsDir}
    echo testsBaseUrl = ${testsBaseUrl}
}

##
 # Clean the destination directory
 #
function cleanDirectories()
{
    title "Cleaning directories..."

    if [ -d ${newNamespaceDir} ]; then
        echo "Cleaning ${newNamespaceDir}..."

        rm -rf ${newNamespaceDir}
    fi

    if [ -d ${testsDir} ]; then
        echo "Cleaning ${testsDir}..."

        rm -rf ${testsDir}
    fi

    if [ -d ${repositoryDir} ]; then
        echo "Cleaning ${repositoryDir}..."

        rm -rf ${repositoryDir}
    fi
}

##
 # Download a new version
 #
function downloadRepository()
{
    title "Downloading repository..."

    echo "downloading ${collectionZipUrl} to ${baseDir}"

    wget ${collectionZipUrl} -O ${collectionZip} >/dev/null 2>&1
}

##
 # Extract from compressed file
 #
function extractZip()
{
    title "Extracting files..."

    echo "extracting $project.zip..."

    unzip ${collectionZip} -d ${rootDir} >/dev/null 2>&1

    rm ${collectionZip}
}

##
 # Copy classes
 #
function copyClasses()
{
    title "Copying classes"

    for class in ${classes[@]}; do
        echo "copying ${oldNamespaceDir}.php/${class}.php..."

        mkdir -p $(dirname ${newNamespaceDir}/${class})

        cp ${oldNamespaceDir}/${class}.php ${newNamespaceDir}/${class}.php
    done
}

##
 # Move contracts
 #
function copyContracts()
{
    title "Copying contracts"

    for contract in ${contracts[@]}; do
        echo "copying ${oldNamespaceDir}.php/${contract}..."

        mkdir -p $(dirname ${newNamespaceDir}/${contract})

        cp ${oldNamespaceDir}/${contract} ${newNamespaceDir}/${contract}
    done
}

##
 # Move traits
 #
function copyTraits()
{
    title "Copying traits"

    for trait in ${traits[@]}; do
        echo "copying ${oldNamespaceDir}.php/${trait}..."

        mkdir -p $(dirname ${newNamespaceDir}/${trait})

        cp ${oldNamespaceDir}/${trait} ${newNamespaceDir}/${trait}
    done
}

##
 # Copy classes and contracts
 #
function copyStubs()
{
    title "Copying stubs"

    for stub in ${stubs[@]}; do
        echo "- stub: ${rootDir}/${stub}"

        echo "  copying ${stubsDir}/${stub} to ${rootDir}/${stub}..."

        mkdir -p $(dirname ${rootDir}/${stub})

        cp ${stubsDir}/${stub} ${rootDir}/${stub}
    done
}

##
 # Fill the alias.php file with the list of aliases
 #
function fillAliases()
{
    title "Filling aliases"

    indent='    '
    aliases='CARRIAGERETURN'

    for class in ${classes[@]}; do
        aliases="${aliases}${indent}${oldNamespace}/${class}::class => ${newNamespace}/${class}::class,CARRIAGERETURN"
    done

    aliases=${aliases//\//\\\\}

    sed -i "" -e "s|/\*--- ALIASES ---\*/|${aliases}|g" $aliasFile
    sed -i "" -e "s|CARRIAGERETURN|\\${carriageReturn}|g" $aliasFile
}

##
 # Copy tests to our tests dir
 #
function getCurrentVersionFromGitHub()
{
    title "Getting current repository version"

    echo "reading $repository..."

    if [ -z "$requestedVersion" ]; then
        collectionVersion=$(git ls-remote $repository | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2 | sort --version-sort | tail -1)
    else
        collectionVersion=$requestedVersion
    fi

    echo "got $vendor/$project version $collectionVersion"
}

##
 # Download tests to tests dir
 #
function downloadTests()
{
    title "Copying tests"

    for test in ${tests[@]}; do
        echo "downloading test ${testsBaseUrl}/${test} to ${testsDir}/${test}..."

        echo "mkdir -p " $(dirname ${testsDir}/${test})

        mkdir -p $(dirname ${testsDir}/${test})

        echo "wget ${testsBaseUrl}/${test} -O ${testsDir}/${test}"

        wget ${testsBaseUrl}/${test} -O ${testsDir}/${test} >/dev/null 2>&1
    done
}

##
 # Rename namespace on all files
 #
function renameNamespace()
{
    title "Renaming namespace from $oldNamespace to $newNamespace..."

    find ${newNamespaceDir} -name "*.php" -exec sed -i "" -e "s|${oldNamespace}|${newNamespace}|g" {} \;
    find ${testsDir} -name "*.php" -exec sed -i "" -e "s|${oldNamespace}|${newNamespace}|g" {} \;

    find ${newNamespaceDir} -name "*.php" -exec sed -i "" -e "s|${oldVanillaNamespace}|${newVanillaNamespace}|g" {} \;
    find ${testsDir} -name "*.php" -exec sed -i "" -e "s|${oldVanillaNamespace}|${newVanillaNamespace}|g" {} \;
}

##
 # Clenup dir
 #
function removeDownloadedRepositoryDir()
{
    title "Cleaning up ${repositoryDir}..."

    rm -rf ${repositoryDir}
}

##
 # Run tests
 #
function runTests()
{
    title "Running tests"

    if [ -f ${rootDir}/composer.lock ]; then
        rm ${rootDir}/composer.lock
    fi

    if [ -d ${rootDir}/vendor ]; then
        rm -rf ${rootDir}/vendor
    fi

    composer install

    vendor/bin/phpunit --coverage-clover=coverage.clover
}

##
 # Run tests
 #
function title()
{
    echo "---------- $1"
}

##
 # Run tests
 #
function showAppTitle()
{
    echo "upgrade.sh v1.0.0 - the Coolection upgrader"
    echo "-------------------------------------------"
}

##
 # Run the app
 #
main $@
