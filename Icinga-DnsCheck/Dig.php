#!/usr/bin/php
<?php




class DigResponse {
	public function __construct($string) {
		$this->parseDigOutput($string);
	}

	protected function parseDigOutput($string) {
/*
; <<>> DiG 9.9.4-RedHat-9.9.4-18.el7_1.1 <<>> NS sysadvice.ch
;; global options: +cmd
;; Got answer:
;; ->>HEADER<<- opcode: QUERY, status: NOERROR, id: 36751
;; flags: qr rd ra; QUERY: 1, ANSWER: 3, AUTHORITY: 0, ADDITIONAL: 4

;; OPT PSEUDOSECTION:
; EDNS: version: 0, flags:; MBZ: 0005 , udp: 4096
;; QUESTION SECTION:
;sysadvice.ch.                  IN      NS

;; ANSWER SECTION:
sysadvice.ch.           5       IN      NS      ns3.sysadvice.ch.
sysadvice.ch.           5       IN      NS      ns1.sysadvice.ch.
sysadvice.ch.           5       IN      NS      ns2.sysadvice.ch.

;; ADDITIONAL SECTION:
ns1.sysadvice.ch.       5       IN      A       85.214.90.145
ns2.sysadvice.ch.       5       IN      A       213.200.244.34
ns3.sysadvice.ch.       5       IN      A       144.76.118.113

;; Query time: 3 msec
;; SERVER: 192.168.2.2#53(192.168.2.2)
;; WHEN: Mon Feb 15 01:58:05 CET 2016
;; MSG SIZE  rcvd: 143
*/
		$this->stats=array();

		foreach(explode("\n\n", $string) as $section) {
			$section = trim($section);
			if($section == '') continue;
			$lines=explode("\n", $section);

			// section 
			if(preg_match('/^; <<>> DiG/', $lines[0])) {
				echo "DIG: $section\n\n---\n\n";
			}
			elseif(preg_match('/^;;\s+Query\s+time:/i', $lines[0])) {
				foreach($lines as $line) {
					if(preg_match('/^;;([^:]+):(.+)$/', $line, $matches)) {
						$matches[1]=strtolower(preg_replace('/\s+/', ' ', trim($matches[1])));
						$matches[2]=trim($matches[2]);
						$this->stats[$matches[1]]=$matches[2];
					}
				}
			}
			elseif(preg_match('/^;;\s+([^:]+):/', $lines[0], $matches)) {
				array_shift($lines);
				$section=implode("\n", $lines);
				if($matches[1]=='OPT PSEUDOSECTION') {
					echo "PSEUDO: $section\n\n---\n\n";
				}
				elseif($matches[1]=='ANSWER SECTION') {
					echo "ANSWER: $section\n\n---\n\n";
				}
				elseif($matches[1]=='ADDITIONAL SECTION') {
					echo "ADDITIOANL: $section\n\n---\n\n";
				}
			}
		}
var_dump($this->stats);
	}
}

/*class DigQuery {
	protected function runDigCommand() {
	}

	public function query() {
	}
}*/


new DigResponse("; <<>> DiG 9.9.4-RedHat-9.9.4-18.el7_1.1 <<>> NS sysadvice.ch
;; global options: +cmd
;; Got answer:
;; ->>HEADER<<- opcode: QUERY, status: NOERROR, id: 36751
;; flags: qr rd ra; QUERY: 1, ANSWER: 3, AUTHORITY: 0, ADDITIONAL: 4

;; OPT PSEUDOSECTION:
; EDNS: version: 0, flags:; MBZ: 0005 , udp: 4096
;; QUESTION SECTION:
;sysadvice.ch.                  IN      NS

;; ANSWER SECTION:
sysadvice.ch.           5       IN      NS      ns3.sysadvice.ch.
sysadvice.ch.           5       IN      NS      ns1.sysadvice.ch.
sysadvice.ch.           5       IN      NS      ns2.sysadvice.ch.

;; ADDITIONAL SECTION:
ns1.sysadvice.ch.       5       IN      A       85.214.90.145
ns2.sysadvice.ch.       5       IN      A       213.200.244.34
ns3.sysadvice.ch.       5       IN      A       144.76.118.113

;; Query time: 3 msec
;; SERVER: 192.168.2.2#53(192.168.2.2)
;; WHEN: Mon Feb 15 01:58:05 CET 2016
;; MSG SIZE  rcvd: 143");
