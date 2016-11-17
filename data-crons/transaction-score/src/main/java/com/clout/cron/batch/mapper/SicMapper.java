package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.Sics;

public class SicMapper implements RowMapper<Sics>  {

	@Override
	public Sics mapRow(ResultSet rs, int arg1) throws SQLException {
		Sics sics = new Sics();
		sics.setId(rs.getInt("id"));
		sics.setCodeDetails(rs.getString("code_details"));
		sics.setCode(rs.getString("code"));
		sics.setMainCategoryCode(rs.getString("main_code_category"));
		sics.setSubCategoryCode(rs.getString("sub_code_category"));
		sics.setSubCategoryCode6(rs.getString("sub_code_category_6"));
		sics.setSubCategoryCode8(rs.getString("sub_code_category_8"));
		return sics;
	}

}
