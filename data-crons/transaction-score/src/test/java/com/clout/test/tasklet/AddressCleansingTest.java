package com.clout.test.tasklet;

import java.util.HashMap;
import java.util.Map;

import org.junit.Test;

import com.clout.cron.batch.util.AddressSanitizationUtil;
import com.clout.cron.batch.util.StringSanitizerUtil;

import junit.framework.Assert;

public class AddressCleansingTest {
	
	private String[] CLEANSE_DATA = new String[] {"avenue", "boulevard", "road", "circle", "street", "drive", "way"};
	
	private final static Map<String, String> normalizeData = new HashMap<String, String>();
	
	static {
		normalizeData.put("avenue", "ave");
		normalizeData.put("boulevard", "blvd");
		normalizeData.put("road", "rd");
		normalizeData.put("circle", "cir");
		normalizeData.put("street", "st");
		normalizeData.put("drive", "dr");
		normalizeData.put("way", "wy");
	}
	
	private final static Map<String, String> normalizeAddress1 = new HashMap<String, String>();
	private final static Map<String, String> normalizeAddress2 = new HashMap<String, String>();
	
	static {
		normalizeAddress1.put("avenue", "ave");
		normalizeAddress1.put("boulevard", "blvd");
		normalizeAddress1.put("road", "rd");
		normalizeAddress1.put("circle", "cir");
		normalizeAddress1.put("street", "st");
		normalizeAddress1.put("drive", "dr");
		normalizeAddress1.put("way", "wy");
		
		
		normalizeAddress2.put("ste", "");
		normalizeAddress2.put("apt", "");
		normalizeAddress2.put("unit", "");
	}
	
	@Test
	public void testJunkInAddress(){
		String address1 = "** moving target **";
		System.out.println(StringSanitizerUtil.sanitize(address1));
		
		//AddressSanitizationUtil.normalizeAddress(AddressSanitizationUtil.sanitize(item.getAddress()));
	}
	
	@Test
	public void testSanitizeAddress() {
		String address1 = "1211 154th blvd.";		
		String address2 = "Apt #3";
		
		address1 = address1.toLowerCase();
		address2 = address2.toLowerCase();
		
		int index =  address1.lastIndexOf(".");
		if(index > 0) {
			address1 = address1.toLowerCase().substring(0, address1.lastIndexOf("."));
		}
		
		for(Map.Entry<String, String> entry : normalizeData.entrySet()) {
			if(address1.toLowerCase().endsWith(entry.getKey())) {
				String tmp = address1.substring(0, address1.toLowerCase().lastIndexOf(entry.getKey()));
				String tmp2 = address1.substring(address1.toLowerCase().lastIndexOf(entry.getKey()) + entry.getKey().length(), address1.length());
				
				System.out.println(tmp);
				System.out.println(tmp2);
				
				address1 = tmp.trim() + " " + entry.getValue() + " " + tmp2.trim();
				
				System.out.println(address1.trim());
			}
		}
		
		
		Assert.assertEquals("1211 154th blvd apt #3", (address1 + " " + address2).trim());
		
	}
	

	@Test
	public void testAddressWithDot(){
		String address1 = "123 Wilshire Ave.";
		
		address1 = address1.toLowerCase().substring(0, address1.lastIndexOf("."));
		
		Assert.assertEquals("123 wilshire ave", address1);
	}
	
	@Test
	public void testAddressWithAvenueInName() {
		String address1 = "123 Avenue Of The Star";
		
		int index =  address1.lastIndexOf(".");
		if(index > 0) {
			address1 = address1.substring(0, address1.lastIndexOf("."));
		}
		
		for(Map.Entry<String, String> entry : normalizeData.entrySet()) {
			if(address1.toLowerCase().endsWith(entry.getKey())) {
				String tmp = address1.substring(0, address1.toLowerCase().lastIndexOf(entry.getKey()));
				String tmp2 = address1.substring(address1.toLowerCase().lastIndexOf(entry.getKey()) + entry.getKey().length(), address1.length());
				
				System.out.println(tmp);
				System.out.println(tmp2);
				
				address1 = tmp.trim() + " " + entry.getValue() + " " + tmp2.trim();
				
				System.out.println(address1.trim());
			}
		}
		
		Assert.assertEquals("123 avenue of the star", address1.toLowerCase());
	}
	
	@Test
	public void testAddressWithAvenueAtEnd() {
		String address1 = "123 Avenue Of The Star Avenue".toLowerCase();
		
		int index =  address1.lastIndexOf(".");
		if(index > 0) {
			address1 = address1.substring(0, address1.lastIndexOf("."));
		}
		
		for(Map.Entry<String, String> entry : normalizeData.entrySet()) {
			if(address1.toLowerCase().endsWith(entry.getKey())) {
				String tmp = address1.substring(0, address1.toLowerCase().lastIndexOf(entry.getKey()));
				String tmp2 = address1.substring(address1.toLowerCase().lastIndexOf(entry.getKey()) + entry.getKey().length(), address1.length());
				
				System.out.println(tmp);
				System.out.println(tmp2);
				
				address1 = tmp.trim() + " " + entry.getValue() + " " + tmp2.trim();
				
				System.out.println(address1.trim());
			}
		}
		
		Assert.assertEquals("123 avenue of the star ave", address1.trim());
	}
}
