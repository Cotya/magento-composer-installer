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
* Rud5G (-)
* therouv (-)
* joshribakoff (-)
* diglin (-)
* vinai (+) (under the condition that solution 3 is implemented for Magento 1)
* gperriard (-)
* ajbonner(+)
* brenbl(-)
* SchumacherFM(+)
*

### Solution 2

Like Solution 1, but we create a compatibility layer afterwards to support magento1, too.


voting:

* Flyingmana (+)
* bragento (+)
* Rud5G (+)
* therouv (+)
* jasperdecbe (+)
* joshribakoff (+)
* diglin (+)
* kalenjordan (+)
* vinai (-) (I prefer solution 3 for Magento 1 composer availability)
* gperriard (+)
* ajbonner (-)
* brenbl (-)
* SchumacherFM (-)
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
* Rud5G(+) - for the implementation of composer features
* joshribakoff (-) - isn't something ideal for end user distribution.
* diglin (+)
* vinai (+)
* ajbonner (-) 
* brenbl (+)
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

maderlock: I'm with avstudnitz. There are parts of the current integration that I like more than option 3 even if given a free choice. For exampkle, having symlinks into the code folders lets me see at a glance what is installed, and those of my team less used to composer can work more or less as they used to. I have therefore not voted, as my vote would be for option 4: continue support of version 1 in a similar vein to at present.

ajbonner: First thing, I don't like the idea of trying to support magento 1 & 2 within the same tool. As mentioned magento 2 fixes a lot of the problems that make composer integration hard and I think it will needlessly complicate the codebase. I feel there should be a composer installer for mage 1, and a version for mage 2, they can share some code, but they ultimately should be two different projects. As per the above comments, I can't see Magento 1 going anywhere anytime soon. I know my company wont adopt magento 2 anytime soon and large e-tailers are not going to be able/want to move quickly to mage2. Composer and composer installer offer such big wins for managing dependencies that it would be a shame too if development simple ceased or slowed down for it in favour of mage 2. So I hope that the problems that exist within composer installer can be looked at as it will benefit more people in the medium term that work that benefits mage 2.

SchumacherFM: +1 for ajbonner

