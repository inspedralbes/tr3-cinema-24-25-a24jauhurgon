FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the source code
COPY . .

# Expose port for Vite
EXPOSE 5173

# Start dev server
CMD ["npm", "run", "dev", "--", "--host"]
