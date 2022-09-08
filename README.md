# phpgdo-tbs

Revival of the
[bright-shadows.net](http://bright-shadows.net/index2.php)
website as a GDOv7 module.

Currently this site is available under http://tbs.wechall.net
    
Please note that this site is a phpgdo demo site with custom theme.
Some of the unit tests are advised to be run in a complete gdo test-suite.
If you want to contribute, please try to install this gdo driven site on your dev machine.
Also, phpgdo is rather new, so there are plenty of bugs lurking.


## phpgdo-tbs: Install

To see how to setup a phpgdo site,
please consult the
[GDO7_INSTALLATION.md](https://github.com/gizmore/phpgdo/blob/main/DOCS/GDO7_INSTALLATION.md)
 

For CLI try this:

    git clone --recursive https://github.com/gizmore/phpgdo
    cd phpgdo
    ./gdoadm.sh configure
    ./gdoadm.sh provide TBS
    ./gdoadm.sh admin username password
    ./gdo_yarn.sh


### phpgdo-tbs: Crawl TBS

To crawl TBS for INPUT/ run the following commands from the /GDO/TBS/bin/ folder. (thx Xaav)

    # todo
    

### phpgdo-tbs: Install crawled backup

    mkdir TBS/INPUT
    cp TBS/DUMP/TBS_public.db TBS/INPUT/TBS.db
    TBS/bin/sqlite2csv.sh

As Admin run the importer in TBS admin section.
An import will take a while. Approx. 20Min.


### Add hidden chall files

The importer merges the folders DUMP/challenges and HIDDEN/ into challenges/
If you wanna help with importing challenges you can take a look at
@TODO: Add a few demo files to DUMP/challenges/ 


#### License

This module and it's content is licensed, and dedicated , to Erik and TBS(TheBlackSheep).

TBS is where i learned a lot of IT skills. THX!


#### Work in Progress

Please note that this is work in progress.

