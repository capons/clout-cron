package com.clout.cron.batch;

import java.io.FileInputStream;
import java.io.IOException;
import java.sql.Date;
import java.sql.Timestamp;
import java.util.Calendar;
import java.util.List;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.springframework.batch.core.BatchStatus;
import org.springframework.batch.core.Job;
import org.springframework.batch.core.JobExecution;
import org.springframework.batch.core.JobParametersBuilder;
import org.springframework.batch.core.launch.JobLauncher;
import org.springframework.context.support.ClassPathXmlApplicationContext;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.jdbc.core.PreparedStatementCallback;
import org.springframework.transaction.annotation.Isolation;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.common.tasklet.QuotaFactory;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.mapper.DataProcessingCronsMapper;
import com.clout.cron.batch.model.DataProcessingCrons;

public class StaticMapsJob implements BatchJob {
	private final static Logger logger = Logger.getLogger(StaticMapsJob.class);


	private String[] args;
	
	public StaticMapsJob() {}
	
	public StaticMapsJob(String[] args) {
		this.args = args;
	}
	
	@Override
	@Transactional(value="localTransactionManager", timeout=60000, isolation=Isolation.READ_UNCOMMITTED)
	public void execute() throws CronJobException {
		
		/*
		 * argument 2
		 *   spring/batch/jobs/staticmap.xml
		 *   spring/batch/jobs/crawler-sitemap.xml
		 */
		if(args.length < 3) {
			logger.error("The job name provided does not exist.  Job is missing parameters.");
			logger.info("Exit Code 1");
			System.exit(1);
		}
		
		try {
			ClassPathXmlApplicationContext context =  new ClassPathXmlApplicationContext("spring/batch/jobs/common-context.xml", "spring/batch/database.xml", args[1]);
	
			logger.info("args " + args[0] + " " + args[1] + " " + args[2]);
			 
			String jobName = args[0];
	
			String propertiesFile = args[2];
			
			Properties props = new Properties();
			
			try {
				props.load(new FileInputStream(propertiesFile));
			} catch (IOException e2) {
				logger.error("Error loading properties file");
				System.exit(1);
			}
	
			
			JdbcTemplate jdbcTemplate = (JdbcTemplate) context.getBean("localJdbcTemplate");
			JobState jobState = (JobState) context.getBean("jobState");
			Integer jobId = 0;
			
			try {
	
				/**
				 * **********
				 * FINISH DOING ONE OFF JOB
				 */
	
				String quota = props.getProperty("google.static.maps.quota");
				Integer lastProcessedStoreId = 0;
				
				List<DataProcessingCrons> crons = null;
				

				String lapseTime = DateFormatUtil.moveBy(-1, Calendar.DAY_OF_MONTH);
				jobId = jdbcTemplate.queryForObject("select min(id) from data_processing_crons where job_name = ? and failed > 0 and last_processed_date < ?", new Object[]{jobName, lapseTime}, Integer.class);
			
				if(jobId == null) {
					/*
					 * No failed jobs, get the last job ran and create a new job
					 */
					jobId = jdbcTemplate.queryForObject("select max(id) from data_processing_crons where job_name = ?", new Object[]{jobName}, Integer.class);
					crons = jdbcTemplate.query("select * from data_processing_crons where id = ? for update", new Object[]{jobId}, new DataProcessingCronsMapper());	
					
					if(crons != null && !crons.isEmpty()) {
						lastProcessedStoreId = crons.get(0).getLastProcessedKey() + Integer.parseInt(quota);
					}
					
					final Integer processingId = lastProcessedStoreId;

					try {
					    PreparedStatementCallback<Boolean> psFunction = (ps) -> {
					        ps.setString(1,jobName);  
					        ps.setInt(2, processingId);  
					        ps.setInt(3, QuotaFactory.getQuota(jobName));
					        ps.setInt(4, 0);
					        ps.setInt(5, 0);
					        ps.setInt(6, 0);
					        ps.setString(7, "Y");
					        ps.setTimestamp(8, new Timestamp(System.currentTimeMillis()));
					        ps.setString(9, "");
					              
					        return (Boolean) ps.execute();    
					    };
					    
					    Boolean success = jdbcTemplate.execute("insert into data_processing_crons (job_name,last_processed_key,quota, already_exist, success, failed, is_active, last_processed_date, service_endpoint) values (?,?,?,?,?,?,?,?,?)", psFunction);					
					    jobId = jdbcTemplate.queryForObject("select max(id) from data_processing_crons where job_name = ?;", new Object[]{jobName}, Integer.class);
					}catch(Exception e) {
						logger.error("Unable to insert data_processing_crons on job startup dueo to " + e.getMessage());
						System.exit(1);
					}	
				}
				else {
					/*
					 * re-run a failed job until failed == 0
					 */
					crons = jdbcTemplate.query("select * from data_processing_crons where id = ? for update", new Object[]{jobId}, new DataProcessingCronsMapper());	
					
					if(crons != null && !crons.isEmpty()) {
						lastProcessedStoreId = crons.get(0).getLastProcessedKey();
					}
					
					final Integer dataProcessingCronsId = jobId;
				    PreparedStatementCallback<Boolean> psFunction = (ps) -> {
				        ps.setTimestamp(1, new Timestamp(System.currentTimeMillis()));
				        ps.setInt(2, dataProcessingCronsId);
				        boolean result = (Boolean) ps.execute();    
				        return result;
				    };
				    
				    Boolean success = jdbcTemplate.execute("UPDATE data_processing_crons set last_processed_date = ? where id = ?", psFunction);
				}

				
				if(crons != null && !crons.isEmpty()) {
					logger.info("Job Launching " + jobName + " quota["+crons.get(0).getQuota()+"] lastProcessedKey[" + crons.get(0).getLastProcessedKey() + "] for jobId " + jobId);
					JobLauncher jobLauncher = (JobLauncher) context.getBean("jobLauncher");
					Job job = (Job) context.getBean(jobName);
	
	
					JobParametersBuilder builder = new JobParametersBuilder();
					builder.addString("quota", quota);
					builder.addString("lastProcessedKey", lastProcessedStoreId.toString());
					builder.addString("jobName", jobName);
					builder.addString("dataProcessingJobId", jobId.toString());
	
					JobExecution execution = jobLauncher.run(job, builder.toJobParameters());
					
					if(execution.getAllFailureExceptions().size() > 0) {
						for(Throwable t : execution.getAllFailureExceptions()) {
							t.printStackTrace();
						}
						recordError(jobState, jobId.toString(), jdbcTemplate);
						throw new CronJobException("Exceptions in job");
					}else {
						logger.info("No exeptions found");
					}
	
					logger.info("Exit Status : " + execution.getStatus());
					
					if(execution.getStatus() == BatchStatus.FAILED) {
						logger.error("Batch job failed");
						recordError(jobState, jobId.toString(), jdbcTemplate);
						throw new CronJobException("Job did not exit correctly.  Job completed with failed status.");
					}else {
						logger.info("Batch job successful");
					}
				}
				logger.info("End of Job " + jobName);
			} catch (Exception e1) {
				logger.info("Error : " + e1.getMessage());
				e1.printStackTrace();
				recordError(jobState, jobId.toString(), jdbcTemplate);
				throw new CronJobException("Job did not exit correctly.  Job completed with failed status.");
			}
		} catch (Exception e1) {
			logger.info("Error : " + e1.getMessage());
			e1.printStackTrace();
			throw new CronJobException("Job did not exit correctly.  Job completed with failed status.");
		}
	}
	
	private void recordError(JobState jobState, String dataProcessingJobId, JdbcTemplate jdbcTemplate) {
	    PreparedStatementCallback<Boolean> psFunction = (ps) -> {
	        ps.setInt(1,jobState.getAlreadyExist());  
	        ps.setInt(2, jobState.getSuccess());  
	        ps.setInt(3, jobState.getFailed());
	        ps.setInt(4, Integer.parseInt(dataProcessingJobId));
	        return (Boolean) ps.execute();    
	    };
	    Boolean success = jdbcTemplate.execute("UPDATE data_processing_crons set already_exist = ?, success = ?, failed = ? where id = ?", psFunction);
	}
}
