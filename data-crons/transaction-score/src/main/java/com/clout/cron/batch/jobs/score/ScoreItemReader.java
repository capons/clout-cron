package com.clout.cron.batch.jobs.score;

import javax.annotation.Resource;

import org.springframework.batch.item.ItemReader;
import org.springframework.batch.item.NonTransientResourceException;
import org.springframework.batch.item.ParseException;
import org.springframework.batch.item.UnexpectedInputException;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.model.Transactions;

@Component("scoreItemReader")
@Scope("step")
public class ScoreItemReader implements ItemReader<Transactions> {

	@Resource(name = "transactionPagingItemReader")
	private ItemReader<Transactions> transactionItemReader;
	
	@Override
	@Transactional("readTransactionTransactionManager")
	public Transactions read()
			throws Exception, UnexpectedInputException, ParseException, NonTransientResourceException {
		Transactions result = transactionItemReader.read();
		return result;
	}

}
