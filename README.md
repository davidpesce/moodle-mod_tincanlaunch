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

The plug in will allow course builders to add launch links to their course sites. The course builder will enter the url of activity and select the launch method to be used. The Rustici and Storyline methods will be implemented. The CMI5 method is out of scope for this project, but may be added later.

The target LRS endpoint will be set as a global property for the Moodle. Later on, we may want to allow reporting to multiple LRS and allow different LRS to be reported to for different learners or cohorts. For now though, that's out of scope. The launch link will need to pull this in when launching the activity.

Basic authorization credentials will be stored on a learner by learner basis to be entered by an administrator, instructor or the user themselves. All credentials will need to be added manually. OAuth is out of scope for this project.

##Approach

##Roadmap

##Project Team

##Useful Links
