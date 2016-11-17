package com.clout.cron.batch;

import java.io.FileInputStream;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.springframework.batch.core.BatchStatus;
import org.springframework.batch.core.Job;
import org.springframework.batch.core.JobExecution;
import org.springframework.batch.core.JobParametersBuilder;
import org.springframework.batch.core.launch.JobLauncher;
import org.springframework.context.ApplicationContext;
import org.springframework.context.support.ClassPathXmlApplicationContext;

/*
 * Cache store id in mongo DB and memcache.  
 * 
 * Stores can be retrieved by name,address,city,state
 * 
 */
public class MemcacheJob implements BatchJob {
	private final static Logger logger = Logger.getLogger(MemcacheJob.class);
	
	private String range;
	private String lastProcessedKey;
	
	public MemcacheJob(String range, String lastProcessedKey) {
		this.range = range;
		this.lastProcessedKey = lastProcessedKey;
	}

	@Override
	public void execute() throws CronJobException {
		String[] springConfig  = 
			{	
				"spring/batch/jobs/common-context.xml",
				"spring/batch/database.xml",
				"spring/batch/jobs/cache-stores.xml"
			};
 
		try {
			ApplicationContext context = 
					new ClassPathXmlApplicationContext(springConfig);
			
			logger.info("Job Launching cachetore job");
			JobLauncher jobLauncher = (JobLauncher) context.getBean("jobLauncher");
			Job job = (Job) context.getBean("cacheJob");
			
			Properties props = new Properties();			
			props.load(new FileInputStream("/opt/cloutjobs/cache/cache.properties"));

			JobParametersBuilder builder = new JobParametersBuilder();
			
			builder.addString("range", range);
			builder.addString("lastProcessedKey", lastProcessedKey);

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

	}
	
}
