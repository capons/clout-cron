package com.clout.cron.batch.staticmap;

import javax.annotation.Resource;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.batch.item.ExecutionContext;
import org.springframework.batch.item.ItemStreamException;
import org.springframework.batch.item.ItemStreamReader;
import org.springframework.batch.item.NonTransientResourceException;
import org.springframework.batch.item.ParseException;
import org.springframework.batch.item.UnexpectedInputException;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Isolation;
import org.springframework.transaction.annotation.Transactional;

import com.clout.cron.batch.SkippableException;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.StaticMapStore;

@Component("customStaticMapItemReader")
@Scope("step")
public class CustomStaticMapItemReader implements ItemStreamReader<StaticMapStore>{
	private final static Logger logger = LoggerFactory.getLogger(CustomStaticMapItemReader.class);
	
	@Resource(name = "pagingItemReader")
	private ItemStreamReader<StaticMapStore> pagingItemReader;
	
	@Resource
	private JobState jobState;

	@Override
	@Transactional(value="transactionManager", timeout=60000, isolation=Isolation.READ_UNCOMMITTED)
	public StaticMapStore read() throws Exception, UnexpectedInputException, ParseException, NonTransientResourceException {
		try {
			return pagingItemReader.read();
		}catch(Exception e) {
			jobState.incrementFailed();
			logger.error("Read Error: " + e.getMessage());
			throw new SkippableException(e.getMessage());
		}
	}

	@Override
	public void open(ExecutionContext executionContext) throws ItemStreamException {
		pagingItemReader.open(executionContext);
	}

	@Override
	public void update(ExecutionContext executionContext) throws ItemStreamException {
		pagingItemReader.update(executionContext);
	}

	@Override
	public void close() throws ItemStreamException {
		pagingItemReader.close();
	}

}
