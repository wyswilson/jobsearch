import mysql.connector
import os
import re

mysqlhost		= "127.0.0.1"
mysqlport		= 3307
mysqluser		= "root"
mysqlpassword	= ""
mysqldb			= "jobsearch"
rootdir			= "C:/jobsearch"
crawlercontentdir	= rootdir + "/crawler/rawcontent"

db = mysql.connector.connect(
	host = mysqlhost,
	port = mysqlport,
	user = mysqluser, passwd = mysqlpassword, database=mysqldb)
cursor = db.cursor()

existingids = []
query1 = """
	SELECT
		jobid
	FROM jobs
"""
cursor.execute(query1)
records = cursor.fetchall()
for record in records:
	jobid = record[0]
	existingids.append(jobid)

files = os.listdir(crawlercontentdir)
for fileid in files:
	f = open(crawlercontentdir + "/" + fileid, encoding="utf8")
	html = f.read()

	if fileid not in existingids:
		print("processing [%s]" % fileid)

		title = ""
		company = ""
		location = ""
		postdate = ""
		jobstext = ""

		titlematch = re.search('<h1 style=\"font-size:1.1em;display:inline;\">(.+?)<\/h1>', html, re.IGNORECASE)
		if titlematch:
			title = titlematch.group(1)
		companymatch = re.search('<span id=\"CompanyNameLabel\" class=\"colorCompany\">(.+?)<\/span>', html, re.IGNORECASE)
		if companymatch:
			company = companymatch.group(1)
		postdatematch = re.search('<span id=\"PostedDate\" class=\"colorDate\">(.+?)<\/span>', html, re.IGNORECASE)
		if postdatematch:
			postdate = postdatematch.group(1)
		jobstextmatch = re.search('<div class=\"normalText\">(.+?)<\/div>', html, re.IGNORECASE)
		if jobstextmatch:
			jobstext = jobstextmatch.group(1)
		locationsmatch = re.search('<a href=\"\/jobs.+?\" class=\"colorLocation\">(.+?)<\/a>', html, re.IGNORECASE)
		if locationsmatch:
			location = locationsmatch.group(1)
		textmatch = re.search('<div class=\"normalText\">(.+?)<\/div>', html, re.IGNORECASE)
		if textmatch:
			text = textmatch.group(1)

		try:
			print("title: " + title)
			print("\tcompany: " + company)
			print("\tpostdate: " + postdate)
			print("\tlocation: " + location)
			query2 = "INSERT INTO jobs (jobid,jobtitle,jobcompany,joblocation,jobdate,jobtext) VALUES (%s,%s,%s,%s,%s,%s)"
			cursor.execute(query2,(fileid,title,company,location,postdate,text))
			db.commit()
		except:
			print("error")
	else:
		print("already exists [%s]" % fileid)

