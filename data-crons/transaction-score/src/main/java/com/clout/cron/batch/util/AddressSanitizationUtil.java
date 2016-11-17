package com.clout.cron.batch.util;

import java.util.HashMap;
import java.util.Map;

import org.apache.commons.lang.StringUtils;
import org.apache.log4j.Logger;

public class AddressSanitizationUtil {
	
	private final static Logger logger = Logger.getLogger(AddressSanitizationUtil.class);
	
	private final static Map<String, String> normalizeAddress1 = new HashMap<String, String>();
	private final static Map<String, String> normalizeAddress2 = new HashMap<String, String>();
	
	static {
		normalizeAddress1.put("alley", "aly");
		normalizeAddress1.put("annex", "anx");
		normalizeAddress1.put("apartment", "apt");
		normalizeAddress1.put("arcade", "arc");
		normalizeAddress1.put("avenue", "ave");
		normalizeAddress1.put("basement", "bsmt");
		normalizeAddress1.put("bayou", "byu");
		normalizeAddress1.put("beach", "bch");
		normalizeAddress1.put("bend","bnd");
		normalizeAddress1.put("bluff", "blf");
		normalizeAddress1.put("bottom", "btm");
		normalizeAddress1.put("boulevard", "blvd");
		normalizeAddress1.put("branch", "br");
		normalizeAddress1.put("bridge", "brg");
		normalizeAddress1.put("brook", "brk");
		normalizeAddress1.put("building", "bldg");
		normalizeAddress1.put("burg", "bg");
		normalizeAddress1.put("bypass", "byp");
		normalizeAddress1.put("camp", "cp");
		normalizeAddress1.put("canyon", "cyn");
		normalizeAddress1.put("cape", "cpe");
		normalizeAddress1.put("causeway", "cswy");
		normalizeAddress1.put("center", "ctr");
		normalizeAddress1.put("circle", "cir");
		normalizeAddress1.put("cliff", "clfs");
		normalizeAddress1.put("cliffs", "clfs");
		normalizeAddress1.put("club", "clb");
		normalizeAddress1.put("corner", "cor");
		normalizeAddress1.put("corners", "cors");
		normalizeAddress1.put("course", "crse");
		normalizeAddress1.put("court", "ct");
		normalizeAddress1.put("courts", "cts");
		normalizeAddress1.put("cove", "cv");
		normalizeAddress1.put("creek", "crk");
		normalizeAddress1.put("crescent", "cres");
		normalizeAddress1.put("crossing", "xing");
		normalizeAddress1.put("dale", "dl");
		normalizeAddress1.put("dam", "dm");
		normalizeAddress1.put("department", "dept");
		normalizeAddress1.put("divide", "dv");
		normalizeAddress1.put("drive", "dr");
		normalizeAddress1.put("estate", "est");
		normalizeAddress1.put("expressway", "expy");
		normalizeAddress1.put("extension", "ext");
		normalizeAddress1.put("falls", "fls");
		normalizeAddress1.put("ferry", "fry");
		normalizeAddress1.put("field", "fld");
		normalizeAddress1.put("fields", "flds");
		normalizeAddress1.put("flat", "flt");
		normalizeAddress1.put("floor", "fl");
		normalizeAddress1.put("ford", "frd");
		normalizeAddress1.put("forest", "frst");
		normalizeAddress1.put("forge", "frg");
		normalizeAddress1.put("fork", "frk");
		normalizeAddress1.put("forks", "frks");
		normalizeAddress1.put("fort", "ft");
		normalizeAddress1.put("freeway", "fwy");
		normalizeAddress1.put("front", "frnt");
		normalizeAddress1.put("garden", "gdns");
		normalizeAddress1.put("gardens", "gdns");
		normalizeAddress1.put("gateway", "gtwy");
		normalizeAddress1.put("glen", "gln");
		normalizeAddress1.put("green", "grn");
		normalizeAddress1.put("grove", "grv");
		normalizeAddress1.put("hanger", "hngr");
		normalizeAddress1.put("harbor", "hbr");
		normalizeAddress1.put("haven", "hvn");
		normalizeAddress1.put("heights", "hts");
		normalizeAddress1.put("highway", "hwy");
		normalizeAddress1.put("hill", "hl");
		normalizeAddress1.put("hills", "hls");
		normalizeAddress1.put("hollow", "holw");
		normalizeAddress1.put("inlet", "inlt");
		normalizeAddress1.put("island", "is");
		normalizeAddress1.put("islands", "iss");
		normalizeAddress1.put("junction", "jct");
		normalizeAddress1.put("key", "ky");
		normalizeAddress1.put("knoll", "knls");
		normalizeAddress1.put("knolls", "knls");
		normalizeAddress1.put("lake", "lk");
		normalizeAddress1.put("lakes", "lks");
		normalizeAddress1.put("landing", "lndg");
		normalizeAddress1.put("lane", "ln");
		normalizeAddress1.put("light", "lgt");
		normalizeAddress1.put("loaf", "lf");
		normalizeAddress1.put("lobby", "lbby");
		normalizeAddress1.put("lock", "lcks");
		normalizeAddress1.put("locks", "lcks");
		normalizeAddress1.put("lodge", "ldg");
		normalizeAddress1.put("lower", "lowr");
		normalizeAddress1.put("manor", "mnr");
		normalizeAddress1.put("meadow", "mdws");
		normalizeAddress1.put("meadows", "mdws");
		normalizeAddress1.put("mill", "ml");
		normalizeAddress1.put("mills", "mls");
		normalizeAddress1.put("mission", "msn");
		normalizeAddress1.put("mount", "mt");
		normalizeAddress1.put("mountain", "mtn");
		normalizeAddress1.put("neck", "nck");
		normalizeAddress1.put("office", "ofc");
		normalizeAddress1.put("orchard", "orch");
		normalizeAddress1.put("parkway", "pkwy");
		normalizeAddress1.put("penthouse", "ph");
		normalizeAddress1.put("pine", "pnes");
		normalizeAddress1.put("pines", "pnes");
		normalizeAddress1.put("place", "pl");
		normalizeAddress1.put("plain", "pln");
		normalizeAddress1.put("plains", "plns");
		normalizeAddress1.put("plaza", "plz");
		normalizeAddress1.put("point", "pt");
		normalizeAddress1.put("port", "prt");
		normalizeAddress1.put("prairie", "pr");
		normalizeAddress1.put("radial", "radl");
		normalizeAddress1.put("ranch", "rnch");
		normalizeAddress1.put("rapid", "rpds");
		normalizeAddress1.put("rapids", "rpds");
		normalizeAddress1.put("rest", "rst");
		normalizeAddress1.put("ridge", "rdg");
		normalizeAddress1.put("river", "riv");
		normalizeAddress1.put("road", "rd");
		normalizeAddress1.put("room", "rm");
		normalizeAddress1.put("shoal", "shl");
		normalizeAddress1.put("shoals", "shls");
		normalizeAddress1.put("shore", "shr");
		normalizeAddress1.put("shores", "shrs");
		normalizeAddress1.put("space", "spc");
		normalizeAddress1.put("spring", "spg");
		normalizeAddress1.put("springs", "spgs");
		normalizeAddress1.put("square", "sq");
		normalizeAddress1.put("station", "sta");
		normalizeAddress1.put("stravenue", "stra");
		normalizeAddress1.put("stream", "strm");
		normalizeAddress1.put("street", "st");
		normalizeAddress1.put("suite", "ste");
		normalizeAddress1.put("summit", "smt");
		normalizeAddress1.put("terrace", "ter");
		normalizeAddress1.put("trace", "trce");
		normalizeAddress1.put("track", "trak");
		normalizeAddress1.put("trafficway", "trfy");
		normalizeAddress1.put("trail", "trl");
		normalizeAddress1.put("trailer", "trlr");
		normalizeAddress1.put("tunnel", "tunl");
		normalizeAddress1.put("turnpike", "tpke");
		normalizeAddress1.put("union", "un");
		normalizeAddress1.put("upper", "uppr");
		normalizeAddress1.put("valley", "vly");
		normalizeAddress1.put("viaduct", "via");
		normalizeAddress1.put("view", "vw");
		normalizeAddress1.put("village", "vlg");
		normalizeAddress1.put("ville", "vl");
		normalizeAddress1.put("vista", "vis");
		normalizeAddress1.put("way", "way");
		normalizeAddress1.put("well", "wls");
		normalizeAddress1.put("wells", "wls");
	}
	
	public static String removeNull(String value) {
		if(value != null && !value.isEmpty()) {
			return StringUtils.trimToEmpty(value.replaceAll("null", ""));
		}
		return "";
	}
	
	public static String normalizeAddress(String address1) {
		address1 = address1.toLowerCase();

		// remove dot
		int index =  address1.lastIndexOf(".");
		if(index > 0) {
			address1 = address1.toLowerCase().substring(0, address1.lastIndexOf("."));
		}
		
		// normalize street, avenue, etc
		for(Map.Entry<String, String> entry : normalizeAddress1.entrySet()) {
			if(address1.toLowerCase().endsWith(entry.getKey())) {
				String tmp = address1.substring(0, address1.toLowerCase().lastIndexOf(entry.getKey()));
				String tmp2 = address1.substring(address1.toLowerCase().lastIndexOf(entry.getKey()) + entry.getKey().length(), address1.length());
				
				address1 = tmp.trim() + " " + entry.getValue() + " " + tmp2.trim();
				
			}
		}
		
		return (address1).trim();
	}
		
	
	public static String normalizeAddress(String address1, String address2) {
		address1 = address1.toLowerCase();

		int index =  address1.lastIndexOf(".");
		if(index > 0) {
			address1 = address1.toLowerCase().substring(0, address1.lastIndexOf("."));
		}
		
		// normalize street, avenue, etc
		for(Map.Entry<String, String> entry : normalizeAddress1.entrySet()) {
			if(address1.toLowerCase().endsWith(entry.getKey())) {
				String tmp = address1.substring(0, address1.toLowerCase().lastIndexOf(entry.getKey()));
				String tmp2 = address1.substring(address1.toLowerCase().lastIndexOf(entry.getKey()) + entry.getKey().length(), address1.length());
				
				address1 = tmp.trim() + " " + entry.getValue() + " " + tmp2.trim();
				
			}
		}
		
		if(address2 != null && !address2.isEmpty()) {
			address2 = address2.toLowerCase();
		}
		
		return (address1 + " " + address2).trim();
	}
	
}
