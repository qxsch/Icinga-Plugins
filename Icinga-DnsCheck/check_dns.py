#!/usr/bin/python

import socket
import getopt, sys, os
import dns.resolver
from timeit import default_timer as timer

class CheckFailedException(Exception):
    def __init__(self,*args,**kwargs):
        Exception.__init__(self,*args,**kwargs)


def compareRecordsWithList(recordList, receivedRecordValue, receivedRecordType, query, record):
     receivedRecordValue=str(receivedRecordValue).lower()
     if receivedRecordValue in recordList:
         recordList.remove(receivedRecordValue)
     else:
         raise CheckFailedException( "DNS " + record + " record check for " + query + " has found an unexpected record " + receivedRecordType + " in the DNS with value " + receivedRecordValue )
    

def usage():
    print("Usage: " + os.path.basename(sys.argv[0]) + " --query=domain.tld [--record=A] [--host=ns.server.tld] --expect=1.2.3.4" )
    print("")
    print("    --query=domain.tld    Record name to query (required)")
    print("    --record=A            Record type to query (optional)")
    print("    --host=ns.server.tld  DNS Server to query  (optional)")
    print("    -H ns.server.tld      DNS Server to query  (optional)")
    print("    --expect=1.2.3.4      Expected value(s), multiple repetitions allowed (required)")
    print("    -v                    Verbose output")
    print("    -h  or  --help        Show this help")
    print("")


def main():
    try:
        opts, args = getopt.getopt(sys.argv[1:], "hvH:", ["help", "host=", "query=", "record=", "expect="])
    except getopt.GetoptError as err:
        print(str(err))
        usage()
        sys.exit(3)
    verbose = False
    dnsHosts = []
    query = ""
    record = "A"
    expectedRecords = []
    for o,a in opts:
        if o == "-v":
            verbose = True
        elif o == "--expect":
            expectedRecords.append(a.lower());
        elif o == "--query":
            query = a;
        elif o == "--record":
            record = a;
        elif o in ("-H", "--host"):
            dnsHosts.append(socket.gethostbyname(a));
        elif o in ("-h", "--help"):
            usage()
            sys.exit()
        else:
            print("Unhandled command line option")
            usage()
            sys.exit(3)
    if query=="":
        print("Please use --query=")
        usage()
        sys.exit(3)
    if record=="":
        print("Please use --record=")
        usage()
        sys.exit(3)
    query = query.lower()
    record = record.upper()
    if not dnsHosts:
        try:
            if verbose:
                print("DNS Nameservers: system defaults")
            t = timer()
            answer = dns.resolver.query(query, record)
            t = timer() - t
        except Exception as err:
            print("DNS query failed: " + str(err) + " | dns_ok=0")
            sys.exit(3)
    else:
        try:
            dnsr = dns.resolver.Resolver()
            dnsr.nameservers = dnsHosts
            if verbose:
                print("DNS Nameservers: " + ", ".join(dnsr.nameservers))
            t = timer()
            answer = dnsr.query(query, record)
            t = timer() - t
        except Exception as err:
            print("DNS query failed: " + str(err) + " | dns_ok=0")
            sys.exit(3)
    if verbose:
        print("DNS Query execution time " + str(t)) 
        print("DNS Returned:")
        for data in answer:
            #print(dir(data))
            if hasattr(data, "address"):
                print("    Record type " + data.__class__.__name__ + " with value " + str(data.address))
            elif hasattr(data, "target"):
                print("    Record type " + data.__class__.__name__ + " with value " + str(data.target))
            elif hasattr(data, "exchange"):
                print("    Record type " + data.__class__.__name__ + " with value " + str(data.exchange))
            elif hasattr(data, "strings"):
                for s in data.strings:
                    print("    Record type " + data.__class__.__name__ + " with value " + str(s))
        print("We are expecting:")
        for r in expectedRecords:
            print("    Record type " + record + " with value " + r)
    if not expectedRecords:
        print("Please set at least an expected record, by using --expect=")
        usage()
        sys.exit(3)
    expectedRecords2 = expectedRecords
    try:
        for data in answer:
            #print(dir(data))
            if hasattr(data, "address"):
                compareRecordsWithList(expectedRecords2, data.address, data.__class__.__name__, query, record)
            elif hasattr(data, "target"):
                compareRecordsWithList(expectedRecords2, data.target, data.__class__.__name__, query, record)
            elif hasattr(data, "exchange"):
                compareRecordsWithList(expectedRecords2, data.exchange, data.__class__.__name__, query, record)
            elif hasattr(data, "strings"):
                for s in data.strings:
                    compareRecordsWithList(expectedRecords2, s, data.__class__.__name__, query, record)
        if expectedRecords2:
            raise CheckFailedException( "DNS has missing " + record + " records for " + query + ": " + ",".join(expectedRecords2) )
    except CheckFailedException as err:
        print(str(err)+ " | dns_ok=0 dns_time=" + "{:.4f}".format(t) + "s")
        sys.exit(2)
    except Exception as err:
        print("Error occurred with message " + str(err) + " | dns_ok=0 dns_time=" + "{:.4f}".format(t) + "s" )
        sys.exit(3)

    print("DNS " + record  + " records are ok for " + query + " | dns_ok=1 dns_time=" + "{:.4f}".format(t) + "s")
    sys.exit(0)


if __name__ == "__main__":
    main()
