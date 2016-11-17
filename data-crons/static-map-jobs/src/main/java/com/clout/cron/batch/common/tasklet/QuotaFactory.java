package com.clout.cron.batch.common.tasklet;

public class QuotaFactory {

	public final static int YELLOWPAGES_QUOTA = 100;
	
	public final static int YELP_QUOTA = 100;

	public final static int GOOGLE_API_STATIC_MAPS_QUOTA = 150000;
	
	public final static int SITEMAP_QUOTA = 50000000;
	
	public final static int USER_ID_QUOTA = 50000000;
	
	public static int getQuota(String jobName) {
		if("yellowpagesDataJob".equals(jobName)) {
			return YELLOWPAGES_QUOTA;
		}
		if("yelpDataJob".equals(jobName)) {
			return YELP_QUOTA;
		}
		else if("userIdUpdateJob".equals(jobName)){
			return USER_ID_QUOTA;
		}
		else if("staticMapJob".equals(jobName)){
			return GOOGLE_API_STATIC_MAPS_QUOTA;
		}
		else if("crawlerSiteMapJob".equals(jobName)){
			return SITEMAP_QUOTA;
		}
		return GOOGLE_API_STATIC_MAPS_QUOTA;
	}
}
