#!/bin/bash

# Define the file extensions to search
extensions=("*.*")

# Process each extension separately
for ext in "${extensions[@]}"; do
    # Find and process files with the current extension recursively
    find . -type f -name "$ext" | while read -r file; do
        echo "Processing $file..."

        # Remove any reference to the transitional DTD line
        sed -i '/http:\/\/www\.w3\.org\/TR\/html4\/transitional\.dtd/d' "$file"

        echo "Finished processing $file."
    done
done

echo "All files processed."
