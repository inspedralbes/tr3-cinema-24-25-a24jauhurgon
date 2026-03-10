FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the source code
COPY . .

# Build frontend
RUN npm run build

# Copy build to a temporary location inside the image
RUN cp -r dist dist_backup

# At runtime, copy files to the shared volume and keep alive
CMD ["sh", "-c", "cp -r /app/dist_backup/. /app/dist/ && echo 'Files synced to /var/www' && tail -f /dev/null"]
