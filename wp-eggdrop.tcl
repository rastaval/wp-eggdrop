set listenport "32578"
# port for your script you want to listen on (you need to set same port in php script)
set password "password"
# password (you need to set same password in php script)
set chan "#staff"

# channel you want to send output to
listen $listenport script botlisten

proc botlisten {idx} {
	control $idx botlisten2
} 

proc botlisten2 {idx args} {

	set args [join $args] 
	set password1 [lindex [split $args] 0]
	set message [join [lrange [split $args] 1 end]]
	
	if {[string match $::password $password1]} {
		putquick "PRIVMSG $::chan :$message"
	} else {
		putlog "Unauthorized person tried to connect to the bot"
	}
}

putlog "WP Eggdrop script loaded!"