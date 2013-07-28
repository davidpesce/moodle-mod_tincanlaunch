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

There are currently two main launch methods in use: the Articlate Storyline method and the Rustici Software method. 
A third method which is likely to gain adopting is the CMI5 method, however this has not yet been finalised. 

##Aims and Scope
The aim of the project is to develop a Moodle plug in to allow Tin Can e-learning activities to be launched from Moodle. This will then be tracked to an external LRS. The plug in will be developed for Moodle 2.5. Support for earlier versions is out of scope for this project.

The plug in will allow course builders to add launch links to their course sites. The course builder will enter the 
url of activity and select the launch method to be used. The Rustici and Storyline methods will be implemented. The 
CMI5 method is out of scope for this project, but may be added later.

The target LRS endpoint will be set as a global property for the Moodle. Later on, we may want to allow reporting to 
multiple LRS and allow different LRS to be reported to for different learners or cohorts. For now though, that's out 
of scope. The launch link will need to pull this in when launching the activity.

Basic authorization credentials will also be stored as global property for this project. It should be noted that
this represents the least secure, but most convienent way of doing Tin Can. It will be relatively easy for a malicious 
and technically skilled Moodle user to access other users' learning records and create false data. A later project
may add oAuth authentication to deal with this issues, but that's out of scope for this project.

The learner's name, account homepage (the URL of the Moodle) and account name will be passed in the launch URL, taken from 
existing Moodle user data. 

Sending a registration id is out of scope for this project. 

##Existing projects for reference
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

##Approach

##Roadmap

##Project Team

##Useful Links
[The Moodle tracker item relating to Tin Can](https://tracker.moodle.org/browse/MDL-35433)

[A post on the Tin Can Google Groups relating to this project](https://groups.google.com/a/adlnet.gov/forum/#!topic/tincanapi-adopters/7ZwtyXOirJo)

[A post on the Tin Can Google Groups relating to security](https://groups.google.com/a/adlnet.gov/forum/#!topic/tincanapi-adopters/kuP13h7AO4I)
