package com.clout.test.tasklet;


import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.junit.Assert;
import org.junit.Test;


public class MatcherTest {
	
	@Test
	public void testCodehausPatternSet() {
		/**
		 * 
BJ&#039;s Wholesale Club	BJ's Wholesale Club
Tony&#039;s Darts Away	Tony's Darts Away
ACH Transaction USAA P&amp;C EXT AUTOPAY	ACH Transaction USAA P&C EXT AUTOPAY
IT&#039;SUGAR UNIVERSAL II	IT'SUGAR UNIVERSAL II
Trader Joe&#039;s	Trader Joe's
DNH*GODADDY.COM	DNHGODADDY.COM
TARGET ID:	TARGET ID
Mojo Hogtown Bar-B-Que	Mojo Hogtown Bar-B-Que
LOWES #00418*	LOWES #00418
Dragonfly Sushi &amp; Sake Co	Dragonfly Sushi & Sake Co
J.Crew	J.Crew
&lt;SQC BRANDON &gt;	SQC BRANDON
Papa John&#039;s	Papa John's
DD/BR 352593 Q35	DD BR 352593 Q35
Moe&#039;s Southwest Grill	Moe's Southwest Grill

		 */
	    Pattern pattern = Pattern.compile("starbucks.*164 7th ave.*brooklyn");
	    
	    Matcher m = pattern.matcher("starbucksxxx 23423 164 7th ave    brooklyn");
	    boolean result = m.matches();
	    Assert.assertTrue(result);
	    
	    pattern = Pattern.compile(".*atm.*deposit.*");
	    m = pattern.matcher("atm deposit");
	    result = m.matches();
	    Assert.assertTrue(result);
	}
	
	
	@Test
	public void testChasePattern() {
	    Pattern pattern = Pattern.compile(".*chase.*");
	    
	    Matcher m = pattern.matcher("CHASE BANK Bill Payment".toLowerCase()+"3233 andy st long beach ca 99009");
	    boolean result = m.matches();
	    Assert.assertTrue(result);
	}
	
	@Test
	public void testSinglePattern() {
	    Pattern pattern = Pattern.compile(".*edeposit.*");
	    
	    Matcher m = pattern.matcher("eDeposit".toLowerCase());
	    boolean result = m.matches();
	    Assert.assertTrue(result);
	}
}
