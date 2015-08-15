## Which Branch to submit my PR to

We use a little different branching strategy then other projects, so finding the right branch to contribute to is a bit confusing for some.
Instead of the usual dev and master branches, our focus is on version branches. The reason is, that people use different versions and usually
keep them to dont break their deployment workflow. We respect this, and so we still support older versions to some extent.

Most PRs are to fix bugs, the best target to submit this fix is to the branch referencing the major.minor version you use.

If you want to submit a new Feature, we prefer the default branch or the highest version branch for this.
But if you use an older version, you can target this. We will care about porting your patch upstream.

This should not be necessary, but if you have a patch which is introducing a backwards compatible break,
then dont submit your Branch as PR, but open an issue with a link to the branch.
We may then say which branch would be best suited to target for a PR,
or even create a new major version Branch for this.
It would also be possible, that we merge it without the PR workflow.

## Keeping the change log up to date
You **must** update the `CHANGELOG.md` file (in the `Unreleased` section) if your change is significant in this sense.
Keep in mind that people are reading the change log to check for new or removed features, backward incompatibilities ("BC breaks")
or security fixes. Do not change the change log for very minor changes.   
If you're unsure, update the change log file.

## Refactoring

Refactoring as part of your PRs may slow down the merge process, as refactoring makes reviewing patches harder.

Refactoring only PRs will usually be postponed to the the next Major release, as they make merging and porting
between branches a lot harder.
There may only be a few cases, where an exception will be made.

## Submitting Bugs

A lot of bugs are very hard to track down, as they often depend on specific combinations of packages and versions.
To make debugging issues easier, always also post the used Version of the Installer.  
Even better, if you can show the used composer.json so we can reproduce the Issue based on it.

# Afterword

dont be afraind, we are open for every kind of contribution, regardless how little it is or how much work it will be for us.
A good prepared contributions will most times get faster into the project, but we will never decline a contribution because
it does not meet our standards, it will only take time till we are able to patch it enough.
Also you can get valuable feedback, how to improve contributions for the next time. :)

