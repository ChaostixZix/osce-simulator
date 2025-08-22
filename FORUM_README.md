# Forum - Social Media Functionality

This is a comprehensive social media forum built with Laravel and Vue.js, featuring Twitter-like functionality including posts, likes, retweets, bookmarks, user following, and notifications.

## 🚀 Features

### Core Social Media Features
- **Posts**: Create, read, update, and delete posts with 280-character limit
- **Interactions**: Like, retweet, and bookmark posts
- **Comments**: Threaded commenting system on posts
- **User Management**: Follow/unfollow users, user profiles
- **Notifications**: Real-time notifications for user interactions
- **Search**: Search posts and users
- **Image Upload**: Support for multiple image uploads

### User Experience
- **Responsive Design**: Mobile-first approach with floating action buttons
- **Real-time Updates**: Dynamic content updates without page refresh
- **Modern UI**: Built with ShadCN Vue components
- **Navigation Tabs**: For you, Following, and Trending feeds

## 🛠 Technical Stack

- **Backend**: Laravel 11 with PHP 8.2+
- **Frontend**: Vue 3 with Inertia.js
- **Database**: MySQL/PostgreSQL with migrations
- **UI Components**: ShadCN Vue
- **Styling**: Tailwind CSS
- **Authentication**: Laravel WorkOS integration

## 📁 File Structure

```
webapp/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ForumController.php
│   │   └── Resources/
│   │       ├── PostResource.php
│   │       └── UserResource.php
│   └── Models/
│       ├── Post.php
│       ├── PostInteraction.php
│       ├── Comment.php
│       ├── Notification.php
│       └── User.php (updated)
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_posts_table.php
│   │   ├── 2024_01_01_000002_create_post_interactions_table.php
│   │   ├── 2024_01_01_000003_create_comments_table.php
│   │   ├── 2024_01_01_000004_create_user_follows_table.php
│   │   ├── 2024_01_01_000007_create_notifications_table.php
│   │   └── 2024_01_01_000008_add_forum_fields_to_users_table.php
│   └── seeders/
│       ├── ForumSeeder.php
│       └── DatabaseSeeder.php (updated)
├── resources/js/
│   └── pages/
│       └── Forum.vue
└── routes/
    └── web.php (updated)
```

## 🗄 Database Schema

### Core Tables
- **posts**: Store forum posts with content, images, and metadata
- **post_interactions**: Track likes, retweets, and bookmarks
- **comments**: Threaded comments on posts
- **user_follows**: User following relationships
- **notifications**: User notifications and alerts
- **users**: Extended user profiles with forum-specific fields

### Key Relationships
- Users can create multiple posts
- Posts can have multiple interactions (likes, retweets, bookmarks)
- Posts can have threaded comments
- Users can follow/unfollow other users
- Notifications are created for user interactions

## 🚀 Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Sample Data
```bash
php artisan db:seed
```

### 3. Storage Setup
```bash
php artisan storage:link
```

### 4. Clear Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 📱 API Endpoints

### Posts
- `POST /api/forum/posts` - Create a new post
- `GET /api/forum/feed` - Get posts for different feeds
- `DELETE /api/forum/posts/{post}` - Delete a post

### Interactions
- `POST /api/forum/posts/{post}/like` - Toggle like
- `POST /api/forum/posts/{post}/retweet` - Toggle retweet
- `POST /api/forum/posts/{post}/bookmark` - Toggle bookmark

### Users
- `POST /api/forum/users/{user}/follow` - Toggle follow
- `GET /api/forum/users/{user}/profile` - Get user profile

### Notifications
- `GET /api/forum/notifications` - Get user notifications
- `PATCH /api/forum/notifications/{notification}/read` - Mark as read

### Search
- `GET /api/forum/search` - Search posts and users

## 🎯 Usage Examples

### Creating a Post
```javascript
// Frontend Vue component
const createPost = async () => {
    const formData = new FormData();
    formData.append('content', postContent.value);
    
    if (selectedImages.value.length > 0) {
        selectedImages.value.forEach(image => {
            formData.append('images[]', image);
        });
    }
    
    const response = await axios.post('/api/forum/posts', formData);
    // Handle response
};
```

### Liking a Post
```javascript
const toggleLike = async (postId) => {
    const response = await axios.post(`/api/forum/posts/${postId}/like`);
    // Update UI based on response
};
```

### Following a User
```javascript
const toggleFollow = async (userId) => {
    const response = await axios.post(`/api/forum/users/${userId}/follow`);
    // Update UI based on response
};
```

## 🔧 Configuration

### Environment Variables
```env
# File upload settings
FILESYSTEM_DISK=public
UPLOAD_MAX_FILESIZE=10M
POST_MAX_SIZE=10M
```

### Storage Configuration
Images are stored in `storage/app/public/forum/posts/` and are accessible via `/storage/forum/posts/`.

## 🧪 Testing

### Sample Data
The ForumSeeder creates:
- 5 sample users with profiles
- 8 sample posts with content
- Sample interactions (likes, retweets, bookmarks)
- Sample comments
- Sample notifications
- Sample follow relationships

### Test Users
- **john@example.com** / password (Verified user)
- **sarah@example.com** / password (Designer)
- **alex@example.com** / password (Backend engineer)
- **maria@example.com** / password (DevOps engineer)
- **david@example.com** / password (Mobile developer)

## 🚀 Future Enhancements

- **Real-time Updates**: WebSocket integration for live notifications
- **Advanced Search**: Elasticsearch integration
- **Media Support**: Video uploads and GIF support
- **Analytics**: Post engagement metrics and user analytics
- **Moderation**: Content moderation and reporting system
- **API Rate Limiting**: Implement proper rate limiting
- **Caching**: Redis caching for better performance

## 🤝 Contributing

1. Follow Laravel coding standards
2. Add tests for new functionality
3. Update documentation for API changes
4. Ensure responsive design for mobile devices

## 📄 License

This project is part of the Laravel + Vue Starter Kit and follows the same licensing terms.

---

**Note**: This forum implementation is designed to be production-ready but may require additional security measures, rate limiting, and performance optimizations for high-traffic applications.
