package com.clout.cron.batch;

import org.apache.log4j.Logger;

public class MainJob {
	private final static Logger logger = Logger.getLogger(MainJob.class);
	
	/*
	 * Cache maps pulled from google
	 */
	private final static String STATIC_MAPS = "staticMapJob";
	
	/*
	 * update the keyword and public_store_key field of store table
	 */
	private final static String STORE_DATA_UPDATE = "storeDataUpdateJob";
	
	/*
	 * Pull data from yellowpages
	 */
	private final static String YELLOWPAGES = "yellowpagesDataJob";
	
	/*
	 * pull data from yelp
	 */
	private final static String YELP = "yelpDataJob";
	
	/*
	 * Generate sitemap
	 */
	private final static String SITEMAP = "crawlerSiteMapJob";
	
	/*
	 * update the id field of the user table 
	 */
	private final static String USERID = "userIdUpdateJob";
	
	/*
	 * These updates the longitude and latitude of stores
	 */
	private final static String CLEAN_STORE = "cleanStoreJob";
	private final static String CLEAN_STORE_SCRAPPER = "cleanScrapperJob";
	
	
	/**
	 * 
	 * @param args
	 */
	public static void main(String[] args) {
		if(args.length > 0) {
			BatchJob job = null;
			String jobName = args[0];
			
			if(STATIC_MAPS.equals(jobName)) {
				job = new StaticMapsJob(args);
			}
			else {
				job = new OtherJobs(args);
			}
			
			try {
				if(job != null) {
					job.execute();
				}
			}
			catch(Exception e) {
				logger.info("Job Failed [" + jobName + "]");
				System.exit(1);
			}
		}
		
		System.exit(0);
	}
}
