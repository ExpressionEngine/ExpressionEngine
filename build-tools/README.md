# Building ExpressionEngine

> NOTE: If you try the `npm` or `gulp` commands below and receive errors about "primordials", "dyld", or "ic4u", you may have incompatible versions of NodeJS, NPM, and/or PHP. Follow the instructions below to make sure you're running the proper version of NodeJS (using ASDF). For PHP version help, please reach out to one of the team members.

## Use ASDF to manage your local node version
Right now we are using [10.0.0](.tool-versions)

### Checking if ASDF is Installed
- In this repo folder in terminal, run `asdf current`.
- If installed, you should see `nodejs    10.0.0    [this folder's path]`
- If not installed, you should see a "command not found" message

### Installing ASDF (Linux or Mac)
- Follow the "Git" installation instructions by copying the `git clone ...` URL [from this page](https://asdf-vm.com/#/core-manage-asdf?id=asdf)
- Follow the "Add to your Shell" instructions - for most of us, that's "macOS" with a "ZSH" shell and "Git" installation method
- Restart or re-init your terminal program
- Run `asdf current` to verify it's installed
- Run `asdf plugin add nodejs`
- Run `asdf list all nodejs` to verify the plugin was installed properly (should see a large list of versions)

### Installing specific NodeJS version with ASDF
- In this repo folder in terminal, run `asdf nodejs install 10.0.0`
- If you see "You should install GnuPG to verify the authenticity of the downloaded archives", follow the steps below:
  - [Download and install GnuPG](https://gnupg.org/download/index.html) (using the "Binary Releases" is easiest)
  - Make sure to import the team's public keys by running:
	- `bash ~/.asdf/plugins/nodejs/bin/import-release-team-keyring`
  - Re-run the `asdf nodejs install 10.0.0`
- Go into this repo folder in terminal and type `asdf local nodejs 10.0.0`
- Type `node -v` to confirm it's running `10.0.0`

## Setting Up Signing Keys
The EE build process uses an openssl private key to sign the build to ensure no tampering during the one-click update process. We do not distribute that private key with this repo for security purposes. If you have access to the company's password vault, you can find it under "ExpressionEngine Signing Key". You need to install this signing key into your ENV vars with the following names:

	RELEASE_KEY_PASSWORD=[password from the vault]
	RELEASE_KEY=[full path to the PEM file you download from the vault]

Example:

	export RELEASE_KEY_PASSWORD="1a2b3c4d5e6f7g8h9i0j"
	export RELEASE_KEY="/Users/macbook/Documents/ExpressionEngineSigningKey.pem"

## Building ExpressionEngine

First, install modules.

	npm install

You'll also need to install Docker in order to build the docs.

Then, you can build ExpressionEngine and its docs with one command from this repo:

	gulp (or ./node_modules/.bin/gulp)

To build just the app:

	gulp app

To build just the docs:

	gulp docs

Example building just the app with your local branch's head:

	gulp app --local --head --version=6.0.0-b.1

You will then find your builds at `/tmp/builds`.

By default, the build script will clone the remote repo and use the tag/reference specified in `build.json`. Here's how you can make it do other things:

## Command-line options

* `--local` Uses your local repo instead of remote for the source of the build. Create a file called `local_config.json` in the root of this repo based off of `local_config.example.json`, and set your local repo paths accordingly.

* `--head` When used with `--local`, uses `HEAD` as the basis for the build. This will be the `HEAD` of whatever branch you currently have checked out.

* `--dirty` When used with `--local`, uses the current working directory of your repo as the basis for the build, including any dirty (changed) files. This will **NOT** include untracked files.

* `--version=5.0.0` Specifies the version of the build, for when there isn't a version set as the `tag` in `build.json`, or you simply want to set it to another one without editing the file. Useful for automating builds for testing.

* `--skip-lint` Skips PHP linting when building the app. Linting can take a little bit, so this is useful when automating builds for testing where we are already doing separate linting.

## Tagging

Typically, we tag with the full version, so `5.0.0`, `5.0.1`, etc.

But when we go to make a Developer Preview, we suffix the tag and version with `-dp.x` where `x` is the number of dev preview we are currenty on. This tells the build script to include the dev preview license (which gives them access to privileged builds via one-click update), and also includes the `src` JavaScript for easier debugging.

So, a full developer preview tag would look like `5.1.0-dp.1`.

TODO: Actually include dev preview license in DP builds.
