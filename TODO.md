# TODO: Refactor DashboardController to use DashboardService

- [ ] Create DashboardService.php in app/Http/Services/ with injected dependencies (CmsBlockRepository, TelemetryRepository, JwstHelper)
- [ ] Move private helper methods base() and getJson() from DashboardController to DashboardService
- [ ] Add getDashboardData() method in DashboardService to handle index() logic (fetch ISS, trend, CSV, CMS blocks)
- [ ] Add getJwstFeed(Request $r) method in DashboardService to handle jwstFeed() logic (process params, fetch/filter JWST data)
- [ ] Add downloadCsv($filename) method in DashboardService to handle downloadCsv() logic (file existence check and response)
- [ ] Update DashboardController to inject DashboardService and delegate methods, removing inline business logic
- [ ] Test the application to ensure routes and views work correctly after refactoring
