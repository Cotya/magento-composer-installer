The Magento Composer Installer has made a great development trough the last year.
The first commit is from 2012-10-27. Today, on the 2013-10-21 we have 303 commits from 15 different [contributors](https://github.com/magento-hackathon/magento-composer-installer/graphs/contributors).
On 2013-09-07 we reached our main goal with a full working magento after executing ```composer.phar install```.
We still have some bugs:
* a nearly cruel workflow
* no benefit from of composers features (e.g. a working cleanup after module update/removal)


Looking at the current development progress of magento2 it feels like a waste of time to stupidly build solutions for the
remaining problems.  
Instead I thought about possible alternatives.


### Solution 1

The easiest solution would be, to ignore the problems and start to look at magento2.
Thanks to the changes of Magento many of the problems we currently have will not exist anymore in magento2.
We don't need to symlink and can use the modules directly from the vendor directory.


voting:

* Flyingmana (+)
* bragento (-)
* therouv (-)
* 

### Solution 2

Like Solution 1, but we create a compatibility layer afterwards to support magento1, too.


voting:

* Flyingmana (+)
* bragento (+)
* therouv (+)
*

### Solution 3

We create a meta module standard in which we only symlink whats really necessary and for example load all php
classes via composer instead of copy/symlink them.

what needs to symlink:

* skin
* js

what can reside in vendor

* code 
* lib

design gets a bit tricky, but with a few changes to magento they should also be able to reside in vendor 
(and get always interpreted as base/default)

In the end this should be easily transferable to magento2

Question (vinai): do you have some proof-of-concept code you could share? I would like to know more about your thoughts on this before casting my vote.

Answer (Flyingmana): 
I have a few out of context code snippets which play with the directory structure in general,
but for this topic only a concept which i will explain a bit more in [draft-solution3.md](draft-solution3.md).


voting:

* Flyingmana (+) - under the condition, we get 30 positive votes on this 
* 
* 




## explanation for voting

everyone should feel free to vote on this.
Simple add a line with your name/nick and a (+) for supporting and a (-) for beeing against it.
You can vote once on every solution. You are also allowed to write a comment or limit your vote to specific conditions.


# Discussion

Vinai: I think its too early to focus on Magento 2 exclusively. Even when it is released, it will be years where Magento 1 still should be supported.
I don't think even new projects will be built on Magento 2 for about a year from now, assuming that the release date of earliy 2014 is kept.

avstudnitz: I am of the same opinion like vinai: it's too early to support magento 2 yet. At the moment, 
what I'd like to see most is improving workflow, fixing of bugs, supporting more of composer's functionality, 
perhaps supporting different deployment methods in a single composer.json, things like that. All in all, I am 
very content with the composer integration as it is now.

nhp: I think Option 2 would be viable, because imho it it way to soon for option 1, like vinai and avstudnitz already said, it will take some time
for magento 2 to get going and become widely used. Option 3 sounds very nice and most like "the composer way" and i have ssen it working in other projects
but i could imagine, that the changes to magento could be more than anybody that wants updateability could accept.

bragento: You shouldn't exclude Magento 1 in any case. The community is just embracing the Idea of using the Composer for Magento projects and should be able to rely on the Composer Installer in the near future.
Even when customers start switching to Magento 2, some customers won't be willing to do so anytime soon. As we are working towards standartizing our deployment processes throughout all releases, we would much appreciate to keep the same (or at least a simmilar) workflow.
Of course you could always start working on a magento2-composer-installer whilst sharing parts of the package with the magento1 version and optimizing other parts for Magento 2.




