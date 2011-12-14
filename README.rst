########################
ExpressionEngine Reactor
########################

Welcome to the ExpressionEngine Reactor project, the home of community contribution to EllisLab's premier web publishing platform.

***************
Getting Started
***************

ExpressionEngine uses CodeIgniter as a submodule, and rather than requiring that you have another account at Beanstalk to have read access to the repo, we have opted to push to a private GitHub repo as another remote.

The tricky part is that Git revisions the ``.gitmodules`` file, so Git will send that change along with push/pulls.  So every time a change is made and pulled, submodules would have to be recommitted, synced, then committed back to the desired location for working locally.

The following steps have been designed to avoid this back and forth, and to provide the smoothest experience for all involved.

**************************
Setting Up Your Local Fork
**************************

1. Fork ExpressionEngine Reactor by clicking the Fork button above
2. Clone your fork locally and initialize Git-Flow (accept Git-Flow's defaults when prompted)::

	git clone git@github.com:<your_name>/ExpressionEngine-Reactor.git expressionengine-reactor
	cd expressionengine-reactor
	git checkout master
	git flow init
	git checkout develop

3. Next we are going to tell Git to ignore changes that we make locally to .gitmodules::

	git update-index --assume-unchanged .gitmodules

4. Next we will change the .gitmodules file to point to GitHub for the CodeIgniter submodule.  (Here assumes we used TextMate, with ``mate .gitmodules``).  Change to the following::

	[submodule "system/codeigniter"]
	    path = system/codeigniter
	    url = git@github.com:EllisLab/CodeIgniter-ELCore-Reactor.git

5. Next we will initialize the submodule and pull in CodeIgniter::

	git submodule init
	git submodule update

6. Add teams to your fork. Go to your fork in GitHub, click Admin at the top right, choose Teams from the left menu and make sure that Owners, Admins, EllisLab Engineers, and ExpressionEngine Reactor are in that list.

7. After installing, add the following line to your config.php to make sure you're using the uncompressed javascript::

	$config['use_compressed_js'] = 'n';

If you cloned using ``--recursive`` or initialized the submodule before changing the url, you will have to synchronize the submodule url before updating the submodule::

	git submodule sync

.. important:: These repositories must remain private and all work under NDA.  Any violation of this policy will result in immediate removal from the ExpressionEngine Reactor program.  Keep in mind that making any of these files public by way of GitHub or any other mechanism would be tantamount to unlawfully redistributing the application.

*****************************
Making and Submitting Changes
*****************************

In your fork, you'll typically always want to start on a feature branch for a change, to refine it until you are happy with it::

	git flow feature start some-feature
	git flow feature publish some-feature

Which will automatically create and checkout a feature/some-feature branch for you.  The second line publishes the feature branch to your GitHub fork.  Now you can commit and make whatever changes you like.

When you are done, you can merge the changes into your develop branch and remove the remote feature branch::

	git flow feature finish some-feature
	git push origin :feature/some-feature

When you are happy with your changes, or want to solicit feedback, send a Pull Request via GitHub to the ExpressionEngine-Reactor private repo, which will create an issue in the EllisLab repo attached to your change sets for discussion or merging.

***************
Advanced Topics
***************

Below are some advanced topics.  If you just plan on working on EE features or fixing EE bugs, you can likely stop reading.

Changes to CodeIgniter
======================

Merging with submodules in Git can already introduce complexity.  Merging from a fork of a fork accompanied by changes to a fork of a parent starts to get icky quickly.  Therefore we recommend not forking and changing CodeIgniter to add or fix features in ExpressionEngine.

If a feature or fix is better suited to CodeIgniter, this can often be done by adding to an existing (or creating a new) CI class extension file in the ExpressionEngine application folder.  If the discussion about the proposed changes leans towards inclusion in CI, the EllisLab team can migrate your change manually to avoid gumming up the GitHub works.

Squashing Commits
=================

Rewriting history is bad.  Unless you're the only one who's seen it.  The EllisLab team commits frequently locally, almost using it like hitting save regularly while working on a word processing document (made obsolete by Lion).  While this is very good for working through changes, the end result often goes through many variations, which can lead to difficulty in trying to grok the entirety of a set of small related change sets.  They may change code from earlier change sets so there is a lot of page flipping, or forking and doing full file diffs to see the whole picture.

If you have not yet shared (pushed) your changes, you can rebase them, squashing them into one or more "clean" change sets that only represents the final state of your desired changes, without the wavy path that may have been taken there.  Its sole purpose is to reduce the number of changesets that must be reviewed so that someone looking at your code does not mistakenly spend time reviewing work that you've already changed within that same day.

Example
*******

Here is a typical example.  Say that you've worked on a new feature, and committed
frequently so that while you were working on it, you had access to all of Git's cool
features to help you manage your work.  After an hour or two, you decide that you're
on the right track and need to get your work to the remote server.

You've had eight commits and you want to squash them all together.

::

	git rebase -i HEAD~7

::

	pick 7340d15 added new function foo()
	pick 62d6254 oops, forgot to include a docblock
	pick 58ad2d5 added a $keepme var for recursive calls
	pick 660820a decided to make $keepme a static var
	pick 08244e0 modified bar() and bat() methods to call foo() now
	pick 794ef09 fixed a typo, $recrusive to $recursive
	pick 54e676f switched $keepme to a class property instead of a static var
	
	# Rebase 565224e..54e676f onto 565224e
	#
	# Commands:
	#  p, pick = use commit
	#  r, reword = use commit, but edit the commit message
	#  e, edit = use commit, but stop for amending
	#  s, squash = use commit, but meld into previous commit
	#  f, fixup = like "squash", but discard this commit's log message
	#  x, exec = run command (the rest of the line) using shell
	#
	# If you remove a line here THAT COMMIT WILL BE LOST.
	# However, if you remove everything, the rebase will be aborted.
	#

So you'd modify that file to read:

::

	pick 7340d15 added new function foo()
	squash 62d6254 oops, forgot to include a docblock
	squash 58ad2d5 added a $keepme var for recursive calls
	squash 660820a decided to make $keepme a static var
	squash 08244e0 modified bar() and bat() methods to call foo() now
	squash 794ef09 fixed a typo, $recrusive to $recursive
	squash 54e676f switched $keepme to a class property instead of a static var

	# Rebase 565224e..54e676f onto 565224e
	#
	# Commands:
	#  p, pick = use commit
	#  r, reword = use commit, but edit the commit message
	#  e, edit = use commit, but stop for amending
	#  s, squash = use commit, but meld into previous commit
	#  f, fixup = like "squash", but discard this commit's log message
	#  x, exec = run command (the rest of the line) using shell
	#
	# If you remove a line here THAT COMMIT WILL BE LOST.
	# However, if you remove everything, the rebase will be aborted.
	#

Then after saving the file, another file will open to give you an choice
to keep all of those original commit messages or to use a single new
commit message.  Unless you have a specific reason, to, it is recommended that you keep
the messages, so that even though your changesets are not preserved,
your workflow and thought processes are, which can be beneficial in
discussion and navigating history.