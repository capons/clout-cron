package com.clout.cron.batch.jobs.match;

import javax.annotation.Resource;

import org.springframework.batch.item.ItemReader;
import org.springframework.batch.item.NonTransientResourceException;
import org.springframework.batch.item.ParseException;
import org.springframework.batch.item.UnexpectedInputException;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.model.TransactionsRaw;

@Component("businessDataItemReader")
@Scope("step")
public class TransactionMatchItemReader implements ItemReader<TransactionsRaw> {

	@Resource(name = "transactionPagingItemReader")
	private ItemReader<TransactionsRaw> transactionItemReader;
	
	@Override
	@Transactional("readTransactionTransactionManager")
	public TransactionsRaw read()
			throws Exception, UnexpectedInputException, ParseException, NonTransientResourceException {
		TransactionsRaw result = transactionItemReader.read();
		return result;
	}

}
