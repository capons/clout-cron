package com.clout.cron.batch.userid.generator;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.batch.item.ItemProcessor;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.model.Users;

/**
 * 
 * @author Ung
 *
 */
@Component("userIdItemProcessor")
@Scope("step")
public class UserIdItemProcessor implements ItemProcessor<Users, Users>{
	
	private final static Logger logger = LoggerFactory.getLogger(UserIdItemProcessor.class);

	@Override
	public Users process(Users item) throws Exception {
		/*
		TODO
		  convert below to code to be auto generated and update DB
		return !empty($id)? "CT".str_pad( dechex($id) , 10 ,'0',STR_PAD_LEFT): “";

		Add ‘0’ to the left of the “ID” and then add “CT” at the start of the string to make the string 10 characters long.  Do nothing if the string is already at 10 characters or over.

		Convert the id from decimal to hexadecimal before padding the id.
		*/
		
		
		String paddedId = "CT" + item.getId();
		
		int pad = 10 - paddedId.length();
		
		StringBuilder builder = new StringBuilder();
		if(pad > 0) {
			for(int i = 0; i < pad; i ++) {
				builder.append("0");
			}  
		}

		item.setCloutId("CT" + builder.toString() + item.getId());
		logger.info("Generated New ID " + item.getCloutId());
		
		return item;
	}

}
