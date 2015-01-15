# Phing Extensions
A set of Phing extensions.

# Overview
This collection of Phing extensions provides:

	* minimizeAndHashTask
	* readSemanticVersionTask
  
## minimizeAndHashTask
minimizeAndHashTask is a Phing extension that does basically three things:     

	* Minimizing JavaScript and CSS files using YUI Compressor.
	* Renaming the minimized files by adding the MD5 checksum into the file name.
	* Updating each reference to JavaScript and CSS files in PHP (or any other) sources.
  	 
## readSemanticVersionTask
readSemanticVersionTask is a Phing extension that asks the user for [Semantic version](http://semver.org/) and validates 
the given input is a valid [Semantic version](http://semver.org/). The version number and it parts are available
in variables for further usage in the Phing build script. 	    

# Installation
We recommend to install setbased/phing via [Composer](https://getcomposer.org/):

```json
{
	"require-dev": {
		"setbased/phing": "1.*"
	}
}
```