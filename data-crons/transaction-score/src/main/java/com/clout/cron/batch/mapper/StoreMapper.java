package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.Store;



/**
 * 
 * @author Ung
 *
 */
public class StoreMapper implements RowMapper<Store>  {
	
	private final static Logger logger = LoggerFactory.getLogger(StoreMapper.class);

	@Override
	public Store mapRow(ResultSet arg0, int arg1) throws SQLException {
		Store store = new Store();
		
		logger.info("In store mapper");

		store.setStoreId(arg0.getLong("id"));
		store.setLatitude(arg0.getString("latitude"));
		store.setLongitude(arg0.getString("longitude"));
		store.setName(arg0.getString("name"));
		store.setAddressLine1(arg0.getString("address_line_1"));
		store.setAddressLine2(arg0.getString("address_line_2"));
		store.setCity(arg0.getString("city"));
		store.setState(arg0.getString("state"));
		store.setZipcode(arg0.getString("zipcode"));
		store.setCountryCode(arg0.getString("_country_code"));	
		store.setPhoneNumber(arg0.getInt("phone_number"));
		store.setWebsite(arg0.getString("website"));
		store.setPublicStoreKey(arg0.getString("public_store_key"));
		store.setKeyWords(arg0.getString("key_words"));
		
		return store;
	}

}
