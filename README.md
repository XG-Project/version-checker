# XG Project version checker #

This tool will connect to GitHub and get the latest version of XG Project.

## Update API ##
The Update API return a JSON encoded string, containing the following information:

* XGP2 latest release
* XGP2 latest development release
* XGP3 latest release
* XGP3 latest development release

If any of them is missing, it will return NULL (for example, if there is no final release for a branch).