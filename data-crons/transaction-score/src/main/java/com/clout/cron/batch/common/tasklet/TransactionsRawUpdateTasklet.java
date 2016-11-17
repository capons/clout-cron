package com.clout.cron.batch.common.tasklet;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.core.StepContribution;
import org.springframework.batch.core.scope.context.ChunkContext;
import org.springframework.batch.core.step.tasklet.Tasklet;
import org.springframework.batch.repeat.RepeatStatus;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.services.TransactionRawServiceInterface;

@Component("transactionsRawUpdateTasklet")
@Scope("step")
public class TransactionsRawUpdateTasklet implements Tasklet {

	private final static Logger logger = Logger.getLogger(TransactionsRawUpdateTasklet.class);
	
	@Resource
	private TransactionRawServiceInterface transactionRawService;
	
	@Resource
	private JobState jobState;
	
	/*
	 * quota for the job
	 */
	@Value("#{jobParameters['range']}")
	private Integer range;
	
	/*
	 * last primary key from previous job
	 */
	@Value("#{jobParameters['lastProcessedKey']}")
	private Integer lastProcessedKey;
	
	@Override
	public RepeatStatus execute(StepContribution contribution, ChunkContext chunkContext) throws Exception {
		logger.info("Updating all processed transactions raw to is_saved = Y ");
		StringBuilder builder = new StringBuilder();
		/*
		for(Integer i : jobState.getTransactionsRawIds()) {
			builder.append(i + ",");			
		}
		*/

		logger.info("transactions raw ids [" + builder.toString() + "]");
		if(!builder.toString().isEmpty()) {
			transactionRawService.updateIsSaved(builder.toString().substring(0, builder.toString().length() - 1));
		}
		return RepeatStatus.FINISHED;
	}

}
