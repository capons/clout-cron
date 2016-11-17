package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.PlaidCategoryMatches;

/**
 * 
 * @author Ung
 *
 */
public class PlaidCategoryMapper implements RowMapper<PlaidCategoryMatches>  {

	@Override
	public PlaidCategoryMatches mapRow(ResultSet rs, int arg1) throws SQLException {
		PlaidCategoryMatches plaidToClout = new PlaidCategoryMatches();
		plaidToClout.setPlaidSubCategoryId(rs.getLong("plaid_sub_category_id"));
		plaidToClout.setCloutSubCategoryId(rs.getLong("_clout_sub_category_id"));
		return plaidToClout;
	}

}
