package com.clout.cron.batch;

import java.io.FileInputStream;
import java.sql.Date;
import java.sql.Timestamp;
import java.util.List;
import java.util.Map;
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
import org.springframework.jdbc.core.PreparedStatementCallback;

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.mapper.TransactionScoreJobsMapper;
import com.clout.cron.batch.model.TransactionScoreJobs;
import com.clout.cron.batch.services.StoreMatchPatternServiceInterface;
import com.clout.cron.specialcase.IdService;

public class MatchTransactionJob implements BatchJob {
	private final static Logger logger = Logger.getLogger(MatchTransactionJob.class);
	
	/*
	 * store ids passed into the job to be cached
	 */
	private String storeIds;
	
	public MatchTransactionJob() {}
	
	public MatchTransactionJob(String storeIds) {
		this.storeIds = storeIds;
	}
	
	@Override
	public void execute() throws CronJobException {
		String[] springConfig  = 
			{	
				"spring/batch/jobs/common-context.xml",
				"spring/batch/database.xml",
				"spring/batch/jobs/transaction-match.xml"
			};
 
		try {
			ApplicationContext context = 
					new ClassPathXmlApplicationContext(springConfig);

			JdbcTemplate jobsJdbcTemplate = (JdbcTemplate) context.getBean("jobsJdbcTemplate");
			JdbcTemplate readTransactionJdbcTemplate = (JdbcTemplate) context.getBean("readTransactionJdbcTemplate");
			JdbcTemplate readStoreJdbcTemplate = (JdbcTemplate) context.getBean("readStoreJdbcTemplate");
			
			Properties props = new Properties();
			props.load(new FileInputStream("/opt/cloutjobs/match/transaction_match.properties"));
			
			String selectMaxTransactionScoreJobsIdQuery = props.getProperty("cron.job.select.max.job.id");
			String transactionScoreJobsQuery = props.getProperty("cron.job.select.transaction.score.jobs");			
			String unprocessedTransactionsRawRecordCountQuery = props.getProperty("cron.job.select.transactionsraw.count");
			String unprocessedTransactionsRawRecordMinIdQuery = props.getProperty("cron.job.select.transactionsraw.minid");
			String unprocessedTransactionsRawRecordMaxIdQuery = props.getProperty("cron.job.select.transactionsraw.maxid");
				
			
			/*
			 * last transactions_raw id that was successfully processed
			 */
			Integer lastProcessedTransactionsRawKey = 0;
			Long lastUncachedStoreId = 0L;
			
			/*
			 * The last transactions match job entry
			 */
			Integer transactionScoreJobsMaxId = jobsJdbcTemplate.queryForObject(selectMaxTransactionScoreJobsIdQuery, new Object[]{}, Integer.class);


			if(transactionScoreJobsMaxId == null) {
				transactionScoreJobsMaxId = 0;
				
				try {
				    PreparedStatementCallback<Boolean> psFunction = (ps) -> {

				        ps.setInt(1, 0);
				        ps.setLong(2, 0);  
				        ps.setInt(3, 0);
				        ps.setInt(4, 0);
				        ps.setInt(5, 0);
				        ps.setInt(6, 0);
				        ps.setInt(7, 0);
				        ps.setInt(8, 0);
				        
				        /*
				         * TODO make this UTC date
				         */
				        ps.setDate(9, new Date(System.currentTimeMillis()));
				              
				        return (Boolean) ps.execute();    
				    };

				    Boolean success = jobsJdbcTemplate.execute("insert into transactions_match_jobs (last_processed_key, last_cached_store_key, success, failed, rejected, matched, new_store, from_cache, last_processed_date) values (?,?,?,?,?,?,?,?,?)", psFunction);					
				    transactionScoreJobsMaxId = jobsJdbcTemplate.queryForObject("select max(id) from transactions_match_jobs;", new Object[]{}, Integer.class);
				}catch(Exception e) {
					logger.error("Unable to insert transactions_match_jobs on job startup dueo to " + e.getMessage());
					System.exit(1);
				}	
			}
			else {
				List<TransactionScoreJobs> lastEnteredJobRecord = jobsJdbcTemplate.query(transactionScoreJobsQuery, new Object[]{transactionScoreJobsMaxId}, new TransactionScoreJobsMapper());
				lastProcessedTransactionsRawKey = lastEnteredJobRecord.get(0).getLastProcessedKey();
				lastUncachedStoreId = lastEnteredJobRecord.get(0).getLastCachedStoreKey();
			}
			
			
			
			logger.info("lastProcessedTransactionsRawKey " + lastProcessedTransactionsRawKey + " transactionScoreJobsMaxId " + transactionScoreJobsMaxId);
			logger.info("selectMaxTransactionScoreJobsIdQuery " + selectMaxTransactionScoreJobsIdQuery);
			logger.info("transactionScoreJobsQuery " + transactionScoreJobsQuery );
			logger.info("selectTransactionsRawMaxIdQuery " + unprocessedTransactionsRawRecordCountQuery);
			logger.info("unprocessedTransactionsRawRecordMaxIdQuery " + unprocessedTransactionsRawRecordMaxIdQuery);
			
			
			
			
			/*
			 * The range of store records to process will be the (maxStoreId - lastUncachedStoreId)
			 * If this is 0 then all stores are already cached and there is nothing to do
			 */
			//Long maxStoreId = readStoreJdbcTemplate.queryForObject("SELECT max(id) from stores", Long.class);
			
			

			/*
			 * Determines the number of records to process - DEPRECATED...using transactions_raw max id instead
			 */
			Integer transactionsRawRecordCount = readTransactionJdbcTemplate.queryForObject(unprocessedTransactionsRawRecordCountQuery, new Object[]{lastProcessedTransactionsRawKey}, Integer.class);
			
			/*
			 * Used to calculate the number of transactions_raw to be processed
			 */
			Integer transactionsRawRecordMaxId = readTransactionJdbcTemplate.queryForObject(unprocessedTransactionsRawRecordMaxIdQuery, new Object[]{}, Integer.class);

			if(transactionsRawRecordMaxId == null) {
				logger.info("Unable to load transactions_raw max id");
				logger.info("Batch job failed");
				System.exit(1);
			}
			
			
			
			/*
			 * The last record processed
			 */
			Integer transactionsRawRecordMinId = readTransactionJdbcTemplate.queryForObject(unprocessedTransactionsRawRecordMinIdQuery, new Object[]{lastProcessedTransactionsRawKey}, Integer.class);

			if(transactionsRawRecordMinId == null) {
				logger.info("No new transaction raw records to process");
				logger.info("Batch job successful");
				System.exit(0);
			}
			
			
			
			
			/*
			 * get plaid category
			 */
			Map<Long, Long> plaidCategoryMatches = (Map<Long, Long>) context.getBean("plaidCategoryMatches");
			
			readTransactionJdbcTemplate.query("SELECT * FROM plaid_category_matches", new Object[] {}, (rs, rowNum) -> {
				plaidCategoryMatches.put(rs.getLong("plaid_sub_category_id"), rs.getLong("_clout_sub_category_id"));
				return null;
			});
			
			logger.debug("plaidCategoryMatches " + plaidCategoryMatches.size());
			
			
			
			
			
			
			/********/
			/*
			 * The available store id to start assigning to new stores
			 */
			Long lastStoreId = readStoreJdbcTemplate.queryForObject("SELECT MAX(id) from stores", Long.class);
			
			/*
			 * The available transactions id to start assigning to new transactions
			 */
			Long lastTransactionsId = readTransactionJdbcTemplate.queryForObject("SELECT MAX(id) from transactions", Long.class);
			
			if(lastStoreId == null) {
				lastStoreId = 0L;
			}
			
			if(lastTransactionsId == null || lastTransactionsId == 0) {
				lastTransactionsId = 0L;
			}
			
			logger.info("Assigning lastTransactionId to " + lastTransactionsId);
			
			
			
			IdService idService = (IdService) context.getBean("idService");
			idService.setLastStoreId(lastStoreId);
			idService.setLastTransactionsId(lastTransactionsId);
			
			/**************************/
				

			
			StoreMatchPatternServiceInterface storeMatchPatternService = (StoreMatchPatternServiceInterface) context.getBean("storeMatchPatternService");
			storeMatchPatternService.preloadMatcher();
			
			
			JobState jobState = (JobState) context.getBean("jobState");
			jobState.setLastProcessedKey(lastTransactionsId.intValue());
			jobState.setLastCachedStoreKey(lastUncachedStoreId);
		
			
			logger.info("Autoincrement store id with " + lastStoreId + ".  Autoincrement transactions id with " + lastTransactionsId + ". Last store id to be cached is " + lastUncachedStoreId);
			
			
			
			try {
				logger.info("Job Launching range["+transactionsRawRecordCount+"] lastProcessedKey[" + transactionsRawRecordMinId + "]");
				JobLauncher jobLauncher = (JobLauncher) context.getBean("jobLauncher");
				Job job = (Job) context.getBean("transactionMatchingJob");
	
				
	
				JobParametersBuilder builder = new JobParametersBuilder();
				builder.addString("range", (transactionsRawRecordMaxId - transactionsRawRecordMinId) + "");
				builder.addString("lastProcessedKey", transactionsRawRecordMinId + "");
				builder.addString("cacheRange", (lastStoreId - lastUncachedStoreId) + "");
				builder.addString("cacheLastProcessedKey", lastUncachedStoreId.toString());
				builder.addString("storeIds", storeIds);
	
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
			}
			catch(Exception e) {
				recordError(jobState, transactionScoreJobsMaxId.toString(), jobsJdbcTemplate);
				throw new CronJobException("Job did not exit correctly.  Job completed with failed status.");
			}
		} catch (Exception e1) {
			logger.info("Error : " + e1.getMessage());
			e1.printStackTrace();
			throw new CronJobException("Job did not exit correctly " + e1.getMessage());
		}

	}

	private void recordError(JobState jobState, String transactionsMatchJobsId, JdbcTemplate jdbcTemplate) {
	    PreparedStatementCallback<Boolean> psFunction = (ps) -> {

	        ps.setInt(1, jobState.getLastProcessedKey());
	        ps.setLong(2, jobState.getLastCachedStoreKey());  
	        ps.setInt(3, jobState.getRejected());
	        ps.setInt(4, jobState.getMatched());
	        ps.setInt(5, jobState.getNewStore());
	        ps.setInt(6, jobState.getCached());
	        ps.setInt(7, jobState.getSuccess());
	        ps.setInt(8, jobState.getFailed());
	        ps.setTimestamp(9, new Timestamp(System.currentTimeMillis()));
	           
	        ps.setInt(10, Integer.parseInt(transactionsMatchJobsId));
	        return (Boolean) ps.execute();    
	    };
	    
	    Boolean success = jdbcTemplate.execute("UPDATE transactions_match_jobs set last_processed_key = ?, last_cached_store_key = ?, rejected = ?, matched = ? , new_store = ?, from_cache = ?, success = ?, failed = ?, last_processed_date = ? where id = ?", psFunction);
	}
	
}
