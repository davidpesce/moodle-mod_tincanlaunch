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

##Approach

##Roadmap

##Project Team

##Useful Links
