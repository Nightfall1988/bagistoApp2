const fs = require('fs');

const manifestPath = 'public/themes/shop/hitexis/build/manifest.json'; // Adjust path to your manifest file

fs.readFile(manifestPath, 'utf8', (err, data) => {
    if (err) {
        console.log(`Error reading manifest file: ${err}`);
        return;
    }

    try {
        const manifest = JSON.parse(data);
        const updatedManifest = {}; // Initialize updatedManifest here

        // Iterate over each key in manifest
        Object.keys(manifest).forEach(originalKey => {
            // Replace "packages/Hitexis/Shop/src/Resources/" with "/src/Resources/"
            const updatedKey = originalKey.replace('packages/Hitexis/Shop/src/Resources/', 'src/Resources/');
            
            // Update src property as well
            manifest[originalKey].src = manifest[originalKey].src.replace('packages/Hitexis/Shop/src/Resources/', 'src/Resources/');
            
            // Assign updated values to the new key in updatedManifest
            updatedManifest[updatedKey] = manifest[originalKey];
        });

        // Write modified manifest back to file
        fs.writeFile(manifestPath, JSON.stringify(updatedManifest, null, 2), 'utf8', (err) => {
            if (err) {
                console.log(`Error writing modified manifest file: ${err}`);
            } else {
                console.log('Manifest file updated successfully.');
            }
        });
    } catch (error) {
        console.log(`Error parsing manifest file: ${error}`);
    }
});