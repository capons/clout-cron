package com.clout.cron.batch.mapper;

import java.sql.ResultSet;
import java.sql.SQLException;

import org.springframework.jdbc.core.RowMapper;

import com.clout.cron.batch.model.YellowpagesLink;

public class YellowpagesLinkMapper implements RowMapper<YellowpagesLink>  {

	@Override
	public YellowpagesLink mapRow(ResultSet rs, int arg1) throws SQLException {
		YellowpagesLink link = new YellowpagesLink();
		link.setId(rs.getInt("id"));
		link.setLink(rs.getString("link"));
		link.setCity(rs.getString("city"));
		link.setState(rs.getString("state"));
		link.setPage(rs.getInt("pages"));
		link.setCategory(rs.getString("category"));
		return link;
	}
	
}
