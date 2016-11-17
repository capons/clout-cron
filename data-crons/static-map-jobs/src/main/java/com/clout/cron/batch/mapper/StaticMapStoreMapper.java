package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.StaticMapStore;

public class StaticMapStoreMapper implements RowMapper<StaticMapStore>  {
	
	private final static Logger logger = LoggerFactory.getLogger(StaticMapStoreMapper.class);

	@Override
	public StaticMapStore mapRow(ResultSet arg0, int arg1) throws SQLException {
		StaticMapStore store = new StaticMapStore();

		/*
		 * Used by static map
		 */
		store.setStoreId(arg0.getInt("id"));

		store.setLatitude(arg0.getString("latitude"));
		store.setLongitude(arg0.getString("longitude"));
		return store;
	}
}
