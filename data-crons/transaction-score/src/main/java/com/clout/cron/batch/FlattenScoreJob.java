package com.clout.cron.batch;

import java.io.FileInputStream;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.springframework.context.ApplicationContext;
import org.springframework.context.support.ClassPathXmlApplicationContext;
import org.springframework.data.mongodb.core.MongoTemplate;
import org.springframework.jdbc.core.JdbcTemplate;

import com.clout.cron.batch.services.SumScoresServiceInterface;

/*
 * Compute the final score for 90 days or 12 months, etc
 */
public class FlattenScoreJob implements BatchJob {
	private final static Logger logger = Logger.getLogger(FlattenScoreJob.class);

	@Override
	public void execute() throws CronJobException {
		String[] springConfig  = 
			{	
				"spring/batch/jobs/common-context.xml",
				"spring/batch/database.xml",
				"spring/batch/jobs/flatten-score-context.xml"
			};
 
		try {
			ApplicationContext context = 
					new ClassPathXmlApplicationContext(springConfig);
			
			logger.info("Job Launching cachetore job");
			
			Properties props = new Properties();			
			props.load(new FileInputStream("/opt/cloutjobs/match/sum-scores.properties"));
			
			String sumScoresMongoCollections = (String) props.get("sum.score.mongo.collections");
			
			
			
			String[] mongoCollections = sumScoresMongoCollections.split(",");

			
			//  JdbcTemplate jobsJdbcTemplate = (JdbcTemplate) context.getBean("jobsJdbcTemplate");
			//  MongoTemplate mongoTemplate = (MongoTemplate) context.getBean("mongoTemplate");
			
			SumScoresServiceInterface sumScoresService = (SumScoresServiceInterface) context.getBean("sumScoresService");
			
			for(String collectionFrequency : mongoCollections) {
				String[] tmp = collectionFrequency.split(" ");
				if(tmp.length > 0) {
					sumScoresService.generateScores(tmp[0], Integer.parseInt(tmp[1]), Integer.parseInt(tmp[2]));
				}
				else {
					sumScoresService.generateScores(collectionFrequency);
				}
				
			}


		} catch (Exception e) {
			logger.info("Error : " + e.getMessage());
			e.printStackTrace();
			throw new CronJobException("Job did not exit correctly " + e.getMessage());
		}

	}

}
