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

# Install a simple server to serve the build
RUN npm install -g http-server

# Serve the dist folder on port 8080
CMD ["http-server", "dist", "-p", "8080", "--proxy", "http://localhost:8080?"]
