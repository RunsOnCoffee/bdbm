#
# A simple makefile starter
#

# make all targets
# typing 'make' will invoke the first target entry in the makefile 
#
all:
	box compile

# clean build targets
#
clean: 
	$(RM) build/*

# install software
#
install:
	cp build/bdbm /usr/local/bin/bdbm
#	cp config/net.runsoncoffee.bdbm "$(HOME)/Library/LaunchAgents/"
#	launchctl -w load "$(HOME)/Library/LaunchAgents/net.runsoncoffee.bdbm"

# uninstall software
uninstall:
	$(RM) /usr/local/bin/bdbm

# purge -- uninstall and remove data files
purge: uninstall