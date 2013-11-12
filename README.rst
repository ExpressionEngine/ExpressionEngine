########################
ExpressionEngine Reactor
########################

Welcome to the ExpressionEngine Reactor project, the home of community
contribution to EllisLab's premier web publishing platform.

**************************
Setting Up Your Local Fork
**************************

1. Fork ExpressionEngine Reactor by clicking the Fork button above
2. Clone your fork locally and make sure you're in the ``develop``
   branch (and if you're using Git-Flow, initialize it now)::

    git clone git@github.com:<your_name>/ExpressionEngine-Reactor.git expressionengine-reactor
    cd expressionengine-reactor
    git checkout develop
    (git flow init)

3. Add teams to your fork. Go to your fork in GitHub, click Admin at the
   top right, choose Teams from the left menu and make sure that Owners,
   Admins, EllisLab Engineers, and ExpressionEngine Reactor are in that
   list.

4. After installing, add the following line to your config.php to make
   sure you're using the uncompressed javascript::

    $config['use_compressed_js'] = 'n';

.. important:: These repositories must remain private and all work under
    NDA.  Any violation of this policy will result in immediate removal
    from the ExpressionEngine Reactor program.  Keep in mind that making
    any of these files public by way of GitHub or any other mechanism
    would be tantamount to unlawfully redistributing the application.

*****************************
Making and Submitting Changes
*****************************

In your fork, you'll typically always want to start on a feature branch
for a change, to refine it until you are happy with it::

  git checkout develop
  git checkout -b feature/<feature_name>

  OR

  git branch feature/<feature_name> develop
  git co feature/<feature_name>

  OR

  git flow feature start <feature_name>


Push your changes up whenever you're ready to::

  git push origin feature/<feature_name>

  OR

  git flow feature publish <feature_name>

When you are ready to send us the code or solicit feedback, send a Pull
Request using GitHub to the ExpressionEngine-Reactor repository.
