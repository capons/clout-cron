package com.clout.cron.batch;

import java.io.FileInputStream;
import java.util.List;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.springframework.batch.core.BatchStatus;
import org.springframework.batch.core.Job;
import org.springframework.batch.core.JobExecution;
import org.springframework.batch.core.JobParametersBuilder;
import org.springframework.batch.core.launch.JobLauncher;
import org.springframework.context.ApplicationContext;
import org.springframework.context.support.ClassPathXmlApplicationContext;
import org.springframework.jdbc.core.JdbcTemplate;

import com.clout.cron.batch.mapper.TransactionScoreJobsMapper;
import com.clout.cron.batch.model.TransactionScoreJobs;

/*
 * Run the cache to cache stores by business id.
 * Load the transactions records and find store id using business id.
 * Update the transactions with no store id.
 * Create a mongo collection and entry for data points by days, months, and lifetime
 * 
 * Lifetime current writes to mongo but should just update DB directly.
 * 
 */
public class ScoreJob implements BatchJob {
	private final static Logger logger = Logger.getLogger(MatchTransactionJob.class);

	@Override
	public void execute() throws CronJobException {
		String[] springConfig  = 
			{	
				"spring/batch/jobs/common-context.xml",
				"spring/batch/database.xml",
				"spring/batch/jobs/score-context.xml"
			};
 

		try {
			ApplicationContext context = 
					new ClassPathXmlApplicationContext(springConfig);

			JdbcTemplate jobsJdbcTemplate = (JdbcTemplate) context.getBean("jobsJdbcTemplate");
			JdbcTemplate readTransactionJdbcTemplate = (JdbcTemplate) context.getBean("readTransactionJdbcTemplate");
			
			Properties props = new Properties();
			props.load(new FileInputStream("/opt/cloutjobs/score/score.properties"));
			

			String selectMaxScoreJobsIdQuery = props.getProperty("store.score.job.max.id");
			String scoreJobsQuery = props.getProperty("store.score.select.job");			
			String unprocessedTransactionsRecordCountQuery = props.getProperty("score.select.transactions.count");
			String unprocessedTransactionsRecordMinIdQuery = props.getProperty("score.select.transactions.minid");
	
			
			logger.info("selectMaxTransactionScoreJobsIdQuery " + selectMaxScoreJobsIdQuery);
			logger.info("transactionScoreJobsQuery " + scoreJobsQuery);
			logger.info("selectTransactionsMaxIdQuery " + unprocessedTransactionsRecordCountQuery);
			
			Integer lastProcessKey = 0;
			Integer transactionScoreJobsMaxId = jobsJdbcTemplate.queryForObject(selectMaxScoreJobsIdQuery, new Object[]{}, Integer.class);

			logger.info("Returned from query");
			
			if(transactionScoreJobsMaxId == null) {
				transactionScoreJobsMaxId = 0;
			}
			else {
				List<TransactionScoreJobs> lastEnteredJobRecord = jobsJdbcTemplate.query(scoreJobsQuery, new Object[]{transactionScoreJobsMaxId}, new TransactionScoreJobsMapper());
				lastProcessKey = lastEnteredJobRecord.get(0).getLastProcessedKey();
			}
			
			logger.info("lastProcessKey " + lastProcessKey + " transactionScoreJobsMaxId " + transactionScoreJobsMaxId);

			
			Integer transactionsRecordCount = readTransactionJdbcTemplate.queryForObject(unprocessedTransactionsRecordCountQuery, new Object[]{lastProcessKey}, Integer.class);
			Integer transactionsRecordMinId = readTransactionJdbcTemplate.queryForObject(unprocessedTransactionsRecordMinIdQuery, new Object[]{lastProcessKey}, Integer.class);
			
			if(transactionsRecordMinId == null) {
				logger.info("No new transaction raw records to process");
				logger.info("Batch job successful");
				System.exit(0);
			}
			
			
			logger.info("Job Launching range["+transactionsRecordCount+"] lastProcessedKey[" + transactionsRecordMinId + "]");
			JobLauncher jobLauncher = (JobLauncher) context.getBean("jobLauncher");
			Job job = (Job) context.getBean("scoreJob");

			
			try {
				JobParametersBuilder builder = new JobParametersBuilder();
				builder.addString("range", transactionsRecordCount + "");
				builder.addString("lastProcessedKey", transactionsRecordMinId + "");

				JobExecution execution = jobLauncher.run(job, builder.toJobParameters());
				
				if(execution.getAllFailureExceptions().size() > 0) {
					for(Throwable t : execution.getAllFailureExceptions()) {
						t.printStackTrace();
					}
				}else {
					logger.info("No exeptions found");
				}

				logger.info("Exit Status : " + execution.getStatus());
				
				if(execution.getStatus() == BatchStatus.FAILED) {
					logger.error("Batch job failed");
					throw new CronJobException("Job did not exit correctly.  Job completed with failed status.");
				}else {
					logger.info("Batch job successful");
				}
			} catch (Exception e) {
				logger.info("Error : " + e.getMessage());
				e.printStackTrace();
				throw new CronJobException("Job did not exit correctly " + e.getMessage());
			}
		} catch (Exception e1) {
			logger.info("Error : " + e1.getMessage());
			e1.printStackTrace();
			throw new CronJobException("Job did not exit correctly " + e1.getMessage());
		}

	}


}
