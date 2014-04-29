# setbased/phing
A set of Phing extensions (well currently one).

# Installation
We recommend to install setbased/phing via [Composer](https://getcomposer.org/):

```json
{
	"require-dev": {
		"setbased/phing": "1.*"
	}
}
```

# minimizeAndHashTask
minimizeAndHashTask is a Phing extension that does basically three things:

* Minimizing JavaScript and CSS files using YUI Compressor.
* Renaming the minimized files by adding the MD5 checksum into the file name.
* Updating each reference to JavaScript and CSS files in PHP (or any other) sources.

## Background

## Example


## Dos and Don'ts
* Do use minimizeAndHashTask for better performance of your website.
* Don't run minimizeAndHashTask directly on your sources. Copy all relevant files of your project to a build directory first.

# License

setbased/phing is released under the MIT public license.