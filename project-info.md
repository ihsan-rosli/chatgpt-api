# Project Info

## Project Goals

Build a historical newspaper search tool for Sinar Harian front pages using OpenAI APIs (with $10,000 USD credit allocation).

### Core Functionality
- **Search Interface**: Users can search using natural language queries (e.g., "what happened on April 7th")
- **Date Extraction**: Use OpenAI GPT API to parse user queries and extract specific dates
- **Newspaper Display**: Display relevant Sinar Harian front pages from any year matching the queried date
- **Content Storage**: Store all Sinar Harian newspaper front pages in organized folders

### Technical Implementation
- **OpenAI APIs to utilize**:
  - GPT API: Natural language processing for search queries
  - Vision API: Extract text/content from newspaper front page images
  - Embeddings API: Create searchable content embeddings
- **Laravel Backend**: Handle file management, database operations, and API integrations
- **Database**: Store newspaper metadata (dates, file paths, extracted content)
- **File Storage**: Organized folder structure for Sinar Harian front page images

### Data Source
- Sinar Harian newspaper front pages (provided manually)
- Historical coverage across multiple years
- Organized by date for efficient retrieval
