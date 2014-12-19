MoodleLaunch
============

A plug in for Moodle that allows the launch of Tin Can content which is then tracked to a separate LRS. 

##Background
The [Tin Can API specification](https://www.tincanapi.co.uk) was released in April 2013 as a replacement for SCORM. 
Tin Can allows for tracking of any learning experience. Tin Can was designed on the premise of a distributed system
communicating via API calls over the internet. This means that whislt it is possible to include a Learner Record 
Store (LRS) and reporting tools inside an LMS like Moodle, it is equally possible for the LRS and reporting tools
to exist as seprate entities outside of the LMS.

This is the first of a series of small bite-sized projects to add Tin Can capability to Moodle. These projects will 
assume that a seprate LRS and reporting tools will be used. This will allow us to take advantage of open source 
Tin Can LRS and reporting tool projects outside of the Moodle community. This first project will deal with 
launching Tin Can e-learning from Moodle.

One of the key issues in Tin Can is launching e-learning activities in such a way that the activity provider knows:
* the LRS endpoint
* authorisation credentials
* user information. 

Currently, the main launch method in use is the [Rustici Software method](https://github.com/RusticiSoftware/launch/blob/master/lms_lrs.md). 
Another method which is likely to gain adopting is the CMI5 method, however this has not yet been finalised. This
plugin uses the Rustici method. 

##What you will need

To use this plugin you will need the following:

* Moodle 2.5 fully set up and running on a server that you have ftp access to 
* Login details for the admin account 
* A Moodle course setup where you would like to add the activity
* A piece of Tin Can compliant e-learning that also implements the launch mechanism outlined HERE, for 
example e-learning produced using Articulate Storyline or Adobe Captivate. This should be put on the internet 
somewhere, perhaps on your Moodle server. * A Tin Can compliant LRS (this plugin has been tested with Wax and 
SCORM Cloud) 
* A Tin Can compliant reporting tool 
* A copy of this plugin.

##Installation

This plugin is installed in the same way as any activity plugin. Simply drop the tincanlaunch folder into your 
mod folder on your moodle and then install via system administration as normal.

It's a known issue that the upgrade script for this plugin needs some attention.If you have any trouble upgrading 
from a previous version of this plugin, and you don't know how to fix the upgrade code, please delete the plugin 
from the mod directory on your server and uninstall the plugin from system administration before trying again. 
Note that this workaround will delete any instances of the plugin that you have set up in your courses.

###Course set up

This plugin can be added to a course like any other course activity. Simply add an activity and select Tin Can 
Launch from the list.

The settings for this module all have help text which can be accessed by clicking the ? icon next to that setting. 
I don't intend to repeat that information here. If any of the help text is unclear, then please raise an issue here 
and suggest an improvement.

##Using the plugin

When the learner clicks the launch link, they are taken to a page listing all of their saved attempts for the 
activity with the most recent attempt at the top and a new attempt button above that. Learners can choose to 
launch a new attempt, or return to a previously saved attempt.

Moodle will pass the e-learning a registration id in the form of a universal unique id representing the previous 
attempt or a newly generated one for a new attempt. It's up to the e-learning what it does with that data, but 
hopefully it will store its bookmarking state on a per registration basis.

Note that the list of attempts is stored in the LRS, rather than Moodle and can therefore be read and modified 
by another LMS or by the learning activity itself. Additionally, if another copy of the launch link is installed 
elsewhere on the Moodle or even on another Moodle, the data will be shared so long as the user email and activity 
id are the same.

##FAQ
So far nobody has asked any questions, but here's some I imagine people might ask:

###Where does the tracking data go?
Tracking data from the learning activity is stored in your LRS and can be retrieved and viewed using any Tin 
Can compliant reporting tool.

It may be that a reporting tool plugin for Moodle is developed in future, or you could write your own.

###On my Moodle, all/some of my users have the same dummy email address. The plugin is behaving oddly. 

The plugin tells the e-learning to store data based on the learner's email address as stored in moodle. It's therefore 
important that the Moodle email address is unique for each user, not just within the scope of the Moodle, but within 
the scope of any system where the tracking data is used or will be used in the future. The safest best is to ensure 
it's universally unique.

With a little work, the plugin can be modified to use the Moodle account id instead.

###Why doesn't the plugin do x y and z?
If you'd like the plugin to do something, please raise an issue and perhaps somebody will do it for you for free. 
If you want to make sure it happens, or get it done quickly, I recommended you hire a developer or add the feature 
yourself. Email [mrdownes@hotmail.com](mailto:mrdownes@hotmail.com) if you'd like to hire me.

###I'm developing a piece of e-learning or authoring tool and want to make sure it will work with Moodle
Great! Please get in touch if you have any questions or want to hire a Tin Can expert. 
[mrdownes@hotmail.com](mailto:mrdownes@hotmail.com)


##Other projects for reference
###Tin Launcher
Tin Launcher is an open source JavaScript tool for launching Tin Can activities using the Rustici launch method. We can
use this as a reference when building the launch URL. This was written by me and we can re-use the code for this 
project if any of it fits. 

[Demo](http://garemoko.github.io/Tin-launcher/)
[Github](https://github.com/garemoko/Tin-launcher)

###SCORM Cloud Moodle Module
The SCORM Cloud Moodle module is designed to intregrate SCORM Cloud into Moodle so that SCORM Cloud is used in
place of Moodle's SCORM player. This also allows the upload of Tin Can packages. In it's current form this module only
works with SCORM Cloud LRS. 

This module is licensed under a GNU 3 license so in theory we could take and re-purpose it to talk to any LRS. 
There's a lot of SCORM related code that we don't need though and it deals with content uploaded to Moodle rather than 
externally hosted content, so I think it makes more sense to start afresh and use this as a reference. 

[Github](https://github.com/RusticiSoftware/SCORMCloud_MoodleModule)

###Jamie Smith's work
Jamie Smith has created a couple of Github projects that work together to allow for Tin Can packages to be
tracked in Moodle as though they are SCORM packages. The aims of his work are different to this project (he's dealing
with content uplaoded to Moodle), but we'll need to consider if and how we build on or integrate with his work. 
Perhaps, for example, this project could be used in conjunction with Jamie's work to allow extenrally hosted Tin Can
activties to be tracked back inside Moodle instead of an external LRS. 

[Github](https://github.com/jgsmitty)

##Useful Links
[The Moodle tracker item relating to Tin Can](https://tracker.moodle.org/browse/MDL-35433)
