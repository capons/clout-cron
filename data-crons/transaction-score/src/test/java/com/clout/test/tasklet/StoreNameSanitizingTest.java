package com.clout.test.tasklet;

import org.apache.commons.lang.StringEscapeUtils;
import org.junit.Assert;
import org.junit.Test;

import com.clout.cron.batch.util.StringSanitizerUtil;

public class StoreNameSanitizingTest {

	@Test
	public void testSanitization() {
		Assert.assertEquals("WEST electric group", StringSanitizerUtil.sanitize("\"WEST\" electric group"));
		Assert.assertEquals("1 LA Plumbers Drain", StringSanitizerUtil.sanitize("#1 LA Plumbers & Drain"));
		Assert.assertEquals("Hanna Morton", StringSanitizerUtil.sanitize("(Hanna & Morton)"));
		Assert.assertEquals("1 Stop Auto Service", StringSanitizerUtil.sanitize("1 Stop Auto Service - CLOSED"));
		Assert.assertEquals("0626 Motorsports", StringSanitizerUtil.sanitize("06-26 Motorsports"));
		Assert.assertEquals("123 Frame", StringSanitizerUtil.sanitize("1-2-3 Frame"));
		Assert.assertEquals("123 Frame", StringSanitizerUtil.sanitize("1-2-3 Frame"));
		Assert.assertEquals("Orchid Fever Inc", StringSanitizerUtil.sanitize("Orchid Fever Inc."));
		Assert.assertEquals("Orantes Law Firm PC", StringSanitizerUtil.sanitize("Orantes Law Firm, P.C."));
		Assert.assertEquals("Original Tommys Hamburgers", StringSanitizerUtil.sanitize("Original Tommy's Hamburgers"));
		Assert.assertEquals("McDonalds", StringSanitizerUtil.sanitize("McDonald&#039;s"));
		Assert.assertEquals("AT&T", StringSanitizerUtil.sanitize("AT&amp;T"));
		
		String value = StringEscapeUtils.unescapeHtml("AT&amp;T");
		Assert.assertEquals("AT&T", value);
		
	}
	
	@Test
	public void testNormalizeAndSanitize() {
		Assert.assertEquals("LUCKY", StringSanitizerUtil.normalizeAndSanitize("2474 LUCKY#756SAN FRANCISCO SAN FRANCISCOCAUS00154"));
		Assert.assertEquals("OFFICE MAX", StringSanitizerUtil.normalizeAndSanitize("2474 OFFICE MAX 3700 GEARY BGEARY CAUS00159"));
		Assert.assertEquals("PHARMACA INTEGRAT", StringSanitizerUtil.normalizeAndSanitize("2474 PHARMACA INTEGRATIVE PHSAN FRANCISCOCAUS00159"));
		Assert.assertEquals("REAL FOOD COMPANY", StringSanitizerUtil.normalizeAndSanitize("2474 REAL FOOD COMPANY SAN FRANCISCOCAUS00154"));
		Assert.assertEquals("ROXIE MARKET DELI", StringSanitizerUtil.normalizeAndSanitize("2474 ROXIE MARKET &amp; DELI RSAN FRANCISCOCAUS00154"));
		Assert.assertEquals("SF Muni SFMTA San", StringSanitizerUtil.normalizeAndSanitize("2474 SF Muni SFMTA San FranciscoCAUS00141"));
		Assert.assertEquals("TARGET", StringSanitizerUtil.normalizeAndSanitize("2474 TARGET"));
		Assert.assertEquals("UBER", StringSanitizerUtil.normalizeAndSanitize("0627 UBER 866-576-1039 CA RECURRING"));
		Assert.assertEquals("THE COFFEE BEAN TEA", StringSanitizerUtil.normalizeAndSanitize("THE COFFEE BEAN &amp; TEA LEAF"));
		Assert.assertEquals("THE COFFEE BEAN LOS", StringSanitizerUtil.normalizeAndSanitize("THE COFFEE BEAN - LOS ANGELES"));
		Assert.assertEquals("LOVE S COUNTRY", StringSanitizerUtil.normalizeAndSanitize("LOVE S COUNTRY00002303"));
		Assert.assertEquals("SUBWAY", StringSanitizerUtil.normalizeAndSanitize("SUBWAY 61351-0"));
		
		

		
	}
}
