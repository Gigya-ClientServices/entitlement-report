Partner Use Metrics:

Input(s): Partner ID, User Key/Secret*

Outputs: Site-by-site report for
  * Account count
  * Last User Login
  * Last Creation
  * Summaries:
    * Aggregate of all counts -- ignoring child sites
    * Newest login
    * Newest Creation

1. Call admin.getUserSites to return a list of all site for the user on the partner ID.
2. Loop through all the sites calling each of the following:
    * admin.getSiteConfig to determine if they are child site -- get SiteID, APIKey
    * If not a child site... call accounts.search for each of the following queries on the site:
      * Account Count -- select count(*) from accounts
      * Last Created User -- SELECT UID, created FROM accounts order by created DESC limit 1
      * Last Logged in User -- SELECT UID, created FROM accounts order by lastLogin DESC limit 1
3. Store data in arrays
4. Format the data into a grid for easy consumption
