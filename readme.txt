OVERVIEW
- A job search engine with simple front-end built in PHP using Elasticsearch and MySQL. Crawler and extractor are written in Perl and Python. The purpose is to illustrate the role of tracking, measurement and evaluation for search quality. The entire process is described in the blog post https://medium.com/@wyswilson/search-quality-in-practice-4415b6084e05

INSTALLATION
- ActivePerl for Windows / Python
- PHP
- Elasticsearch
- MySQL

CONFIGURATION AND OTHER INSTRUCTIONS
- If you're on Windows, run the .bat file in the bin folder in elasticsearch-7.8.0.
- Configure the folder path, DB connection details and Elasticsearch index path in define.php, crawler/crawler.pl and crawler/extrator.py.
- All the crawled jobs are available to you in crawler/rawcontent/ for your convenience.
- The SQL queries to create the jobs and logs tables in MySQL are included in jobsearch.sql
- The data extracted from the job ad HTML are also included in jobsearch.sql for your convenience. I know committing data into git is not good practice, but they're there to help readers get up and running quick and move on to the search quality aspects if they struggle with acquiring the data.
- The search metrics discussed in the article are stored in searchmetrics.txt
- The main search UI is index.php which you can access once hosted on a web server such as Apache httpd.
- The files in crawler/ folder is to crawl jobs and extract structured text into MySQL.