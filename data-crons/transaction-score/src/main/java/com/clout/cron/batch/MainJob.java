package com.clout.cron.batch;

import org.apache.log4j.Logger;

/*
 * Main job to launch other jobs
 */
public class MainJob {
	private final static Logger logger = Logger.getLogger(MainJob.class);
	
	private final static String TRANSACTION_MATCH = "match";
	private final static String CACHE = "cache";
	private final static String FLATTEN_SCORE = "flattenScore";
	private final static String SCORE = "score";

	
	/**
	 * 
	 * @param args
	 */
	public static void main(String[] args) {
		if(args.length > 0) {
			BatchJob job = null;
			String jobName = args[0];
			
			if(TRANSACTION_MATCH.equals(jobName)) {
				if(args.length == 2) { 
					job = new MatchTransactionJob(args[1]);
				}
				else {
					job = new MatchTransactionJob("1");
				}
			}
			else if(CACHE.equals(jobName)) {
				if(args.length == 3) {
					// range & last cached store id
					job = new MemcacheJob(args[1], args[2]);
				}
				else {
					logger.info("Job Failed [" + jobName + "].  Must provide last cached store id and the id range (max id - last cached store id)");
					System.exit(1);
				}
			}
			else if(SCORE.equals(jobName)) {
				job = new ScoreJob();
			}
			else if(FLATTEN_SCORE.equals(jobName)) {
				job = new FlattenScoreJob();
			}
			else {
				logger.info("No matching job name [" + jobName + "]");
				System.exit(1);
			}
			
			try {
				if(job != null) {
					job.execute();
					System.exit(0);
				}
			}
			catch(Exception e) {
				logger.info("Job Failed [" + jobName + "]");
				System.exit(1);
			}
		}
		
		
	}
}
