<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinar Harian Newspaper Search</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-red-600 mb-2">Sinar Harian Historical Search</h1>
            <p class="text-gray-600">Search through Sinar Harian newspaper archives by date</p>
        </div>

        <!-- Search Section -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-semibold mb-4">Search Newspapers</h2>
                
                <div class="flex gap-4 mb-4">
                    <input 
                        type="text" 
                        id="searchQuery" 
                        placeholder="e.g., 'What happened on April 7th?' or 'Show me news from 7 April 2023'"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    >
                    <button 
                        id="searchBtn" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500"
                    >
                        Search
                    </button>
                </div>

                <!-- Loading indicator -->
                <div id="loading" class="hidden text-center py-4">
                    <div class="inline-flex items-center">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-red-600"></div>
                        <span class="ml-2">Searching newspapers...</span>
                    </div>
                </div>

                <!-- Search Results -->
                <div id="searchResults" class="mt-6"></div>
            </div>

        </div>
    </div>

    <script>
        // Setup CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Search functionality
        $('#searchBtn').click(function() {
            const query = $('#searchQuery').val().trim();
            
            if (!query) {
                alert('Please enter a search query');
                return;
            }

            $('#loading').removeClass('hidden');
            $('#searchResults').empty();

            $.ajax({
                url: '{{ route("api.newspaper.search") }}',
                method: 'POST',
                data: { query: query },
                success: function(response) {
                    $('#loading').addClass('hidden');
                    displaySearchResults(response);
                },
                error: function(xhr) {
                    $('#loading').addClass('hidden');
                    const error = xhr.responseJSON?.error || 'Search failed';
                    $('#searchResults').html(`
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <strong>Error:</strong> ${error}
                        </div>
                    `);
                }
            });
        });

        // Allow Enter key to trigger search
        $('#searchQuery').keypress(function(e) {
            if (e.which === 13) {
                $('#searchBtn').click();
            }
        });


        function displaySearchResults(response) {
            let html = '';

            if (response.newspapers && response.newspapers.length > 0) {
                html += `<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    <strong>Success:</strong> ${response.message}
                </div>`;

                html += '<div class="grid gap-4">';
                response.newspapers.forEach(newspaper => {
                    html += `
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="text-lg font-semibold">Sinar Harian - ${newspaper.published_date}</h3>
                                <span class="text-sm text-gray-500">${newspaper.file_name}</span>
                            </div>
                            
                            <div class="mb-3">
                                <img src="${newspaper.public_url}" alt="Newspaper ${newspaper.published_date}" 
                                     class="w-full max-w-md mx-auto rounded border shadow-sm cursor-pointer"
                                     onclick="window.open('${newspaper.public_url}', '_blank')">
                            </div>
                            
                            ${newspaper.extracted_content ? `
                                <div class="bg-gray-50 p-3 rounded text-sm">
                                    <strong>Content Preview:</strong><br>
                                    ${newspaper.extracted_content}
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                html += '</div>';
            } else {
                html = `<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
                    <strong>No Results:</strong> ${response.message || 'No newspapers found for your search.'}
                </div>`;
            }

            $('#searchResults').html(html);
        }

    </script>
</body>
</html>