<?php

echo "\033[32m"; // Set text color to green

echo <<<EOT
 ____  _   _ ____    _____  __        __
|  _ \| | | |  _ \  |_   _|_\ \      / /
| |_) | |_| | |_) |   | | / \ \ /\ / / 
|  __/|  _  |  __/    | |/ _ \ V  V /  
|_|   |_| |_|_|       |_/_/ \_\_/\_/   
                                       
EOT;

echo "\033[0m"; // Reset text color

echo "\n\nWelcome to PHP-TW!\n";
echo "Your project has been created successfully.\n\n";
echo "To get started:\n";
echo "1. Navigate to your project directory\n";
echo "2. Run 'php wand serve' to start the development server\n";
echo "3. Open http://localhost:6969 in your browser\n\n";
echo "Happy coding!\n";
