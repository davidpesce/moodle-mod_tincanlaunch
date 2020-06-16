tincanlaunch
============

A Moodle plug-in that allows the launch of xAPI content using a separate LRS. 

## Background
The [xAPI Specification](https://github.com/adlnet/xAPI-Spec) was released in 2013 and allows for tracking 
of learning experiences. xAPI was designed on the premise of a distributed system communicating via 
API calls over the internet. This means that whilst it is possible to include a Learner Record Store (LRS) and 
reporting tools inside an LMS like Moodle, it is equally possible for the LRS and reporting tools to exist as 
seprate entities outside of the LMS.

This projects will assume that a separate LRS and reporting tool are used. This will allow us to take 
advantage of an open-source LRS and reporting tool projects outside of the Moodle community.

One of the key issues in xAPI is launching content in such a way that the activity provider knows:
* the LRS endpoint
* authorisation credentials
* user information


This project will utilize the most common launch method:
* [Rustici Launch Method](https://github.com/RusticiSoftware/launch/blob/master/lms_lrs.md)

A second method will be considered as development continues, [cmi5](http://aicc.github.io/CMI-5_Spec_Current/). 
 

## What you will need
To use this plugin you will need the following:
* A working instance of a [supported Moodle version](https://docs.moodle.org/dev/Releases) 3.5, 3.7, or 3.8+
* A [supported PHP version](https://www.php.net/supported-versions.php) (as of this writing the supported versions of PHP are 7.2-7.4)
* Moodle administrative access
* Web accessible xAPI-compliant content that implements the launch mechanism outlined above (Articulate Storyline/ Adobe Captivate)
* An xAPI-compliant LRS (LearningLocker, Watershed, SCORM Cloud)


## Installation (Recommended)
It is recommended to get this plugin from the Moodle Plugins Database (https://moodle.org/plugins/mod_tincanlaunch)

This plugin is installed in the same way as any activity plugin. Download the zip file and navigate to Moodle
System administration > Plugins > Install plugins.

## Installation from Github (Developer)

This is recommended only for developers or if you need the very latest versions. 

The plugin has one submodule dependencies and will NOT work directly from a clone. Go into the plugin folder
and type the following command:

```git submodule update --init --recursive```

The plugin is now ready for use.

### Course set up
This plugin can be added to a course like any other course activity. Simply add an activity and select Tin Can 
Launch from the list.

The settings for this module all have help text which can be accessed by clicking the ? icon next to that setting. 

## Using the plugin
When the learner clicks the launch link, they are taken to a page listing all of their prior attempts for that 
activity. The most recent attempt is at the top and a new attempt button above that. Learners can choose to 
launch a new attempt, or return to a previously saved attempt. This can also be disabled to only show the most
recent attempt.

Moodle will pass the e-learning a registration id in the form of a universal unique id representing the previous 
attempt or a newly generated one for a new attempt. It's up to the e-learning what it does with that data, but 
hopefully it will store its bookmarking state on a per registration basis.

Note that the list of attempts is stored in the LRS, rather than Moodle and can therefore be read and modified 
by another LMS or by the learning activity itself. Additionally, if another copy of the launch link is installed 
elsewhere on the Moodle or even on another Moodle, the data will be shared so long as the user email and activity 
id are the same.

## FAQ

### Where does the tracking data go?
Tracking data from the learning activity is stored in your LRS and can be retrieved and viewed using an xAPI-compliant reporting tool.


### Why doesn't the plugin do x, y, or z?
If you'd like the plugin to do something, please raise an issue and perhaps somebody will do it for you for free. 
If you want to make sure it happens, or get it done quickly, I recommended you hire a developer or add the feature 
yourself. Email [david.pesce@exputo.com](mailto:david.pesce@exputo.com) if you'd like to hire us.


## Other projects for reference
### Tin Launcher
Tin Launcher is an open source JavaScript tool for launching Tin Can activities using the Rustici launch method. We can
use this as a reference when building the launch URL. This was written by me and we can re-use the code for this 
project if any of it fits. 

[Demo](http://garemoko.github.io/Tin-launcher/)

[Github](https://github.com/garemoko/Tin-launcher)

### SCORM Cloud Moodle Module
The SCORM Cloud Moodle module is designed to integrate SCORM Cloud into Moodle so that SCORM Cloud is used in
place of Moodle's SCORM player. This also allows the upload of Tin Can packages. In it's current form this module only
works with SCORM Cloud LRS. 

This module is licensed under a GNU 3 license so in theory we could take and re-purpose it to talk to any LRS. 
There's a lot of SCORM related code that we don't need though and it deals with content uploaded to Moodle rather than 
externally hosted content, so I think it makes more sense to start afresh and use this as a reference. 

[Github](https://github.com/RusticiSoftware/SCORMCloud_MoodleModule)

### Jamie Smith's work
Jamie Smith has created a couple of Github projects that work together to allow for Tin Can packages to be
tracked in Moodle as though they are SCORM packages. The aims of his work are different to this project (he's dealing
with content uplaoded to Moodle), but we'll need to consider if and how we build on or integrate with his work. 
Perhaps, for example, this project could be used in conjunction with Jamie's work to allow extenrally hosted Tin Can
activties to be tracked back inside Moodle instead of an external LRS. 

[Github](https://github.com/jgsmitty)

## Useful Links
[The Moodle tracker item relating to xAPI (TinCan)](https://tracker.moodle.org/browse/MDL-35433)

## Reporting issues
Please report any issues with this plugin here: https://github.com/davidpesce/moodle-mod_tincanlaunch/issues
Please provide screenshots of your settings (both at plugin and instance level) and a link to your content. 

The majority of issues are caused by incorrect settings. You can see previous closed issues here: https://github.com/garemoko/moodle-mod_tincanlaunch/issues?q=is%3Aissue+is%3Aclosed
