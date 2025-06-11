import tkinter as tk
from tkinter import filedialog, messagebox
import os
import re

class ConfigEditorApp:
    """
    A GUI application to edit configuration values in a set of website files.
    """
    def __init__(self, root):
        self.root = root
        self.root.title("Website Configuration Editor")
        self.root.geometry("600x450")
        self.root.configure(bg="#f0f0f0")

        # --- Variables ---
        self.project_dir = tk.StringVar()
        self.db_server = tk.StringVar(value="localhost")
        self.db_username = tk.StringVar(value="root")
        self.db_password = tk.StringVar()
        self.db_name = tk.StringVar()
        self.api_key = tk.StringVar()
        self.page_title = tk.StringVar()

        # --- UI Layout ---
        main_frame = tk.Frame(self.root, padx=20, pady=20, bg="#f0f0f0")
        main_frame.pack(fill=tk.BOTH, expand=True)

        # --- Project Directory Section ---
        dir_frame = tk.LabelFrame(main_frame, text="1. Select Website Folder", padx=10, pady=10, bg="#f0f0f0")
        dir_frame.pack(fill=tk.X, pady=(0, 15))

        tk.Entry(dir_frame, textvariable=self.project_dir, width=60, state='readonly').pack(side=tk.LEFT, fill=tk.X, expand=True, ipady=4)
        tk.Button(dir_frame, text="Browse...", command=self.browse_directory).pack(side=tk.LEFT, padx=(5, 0))

        # --- Configuration Fields ---
        config_frame = tk.LabelFrame(main_frame, text="2. Enter New Values", padx=15, pady=15, bg="#f0f0f0")
        config_frame.pack(fill=tk.X, pady=(0, 15))
        config_frame.grid_columnconfigure(1, weight=1)

        # Database fields
        tk.Label(config_frame, text="DB Server:", bg="#f0f0f0").grid(row=0, column=0, sticky="w", pady=2)
        tk.Entry(config_frame, textvariable=self.db_server).grid(row=0, column=1, sticky="ew", pady=2)

        tk.Label(config_frame, text="DB Username:", bg="#f0f0f0").grid(row=1, column=0, sticky="w", pady=2)
        tk.Entry(config_frame, textvariable=self.db_username).grid(row=1, column=1, sticky="ew", pady=2)

        tk.Label(config_frame, text="DB Password:", bg="#f0f0f0").grid(row=2, column=0, sticky="w", pady=2)
        tk.Entry(config_frame, textvariable=self.db_password, show="*").grid(row=2, column=1, sticky="ew", pady=2)

        tk.Label(config_frame, text="DB Name:", bg="#f0f0f0").grid(row=3, column=0, sticky="w", pady=2)
        tk.Entry(config_frame, textvariable=self.db_name).grid(row=3, column=1, sticky="ew", pady=2)
        
        # API Key field
        tk.Label(config_frame, text="API Key:", bg="#f0f0f0").grid(row=4, column=0, sticky="w", pady=(10, 2))
        tk.Entry(config_frame, textvariable=self.api_key).grid(row=4, column=1, sticky="ew", pady=(10, 2))

        # Page Title field
        tk.Label(config_frame, text="Page Title:", bg="#f0f0f0").grid(row=5, column=0, sticky="w", pady=2)
        tk.Entry(config_frame, textvariable=self.page_title).grid(row=5, column=1, sticky="ew", pady=2)

        # --- Submit Button ---
        submit_button = tk.Button(main_frame, text="Update Website Files", command=self.process_updates, bg="#4CAF50", fg="white", font=("Arial", 12, "bold"))
        submit_button.pack(pady=10, ipady=8, fill=tk.X)

    def browse_directory(self):
        """Opens a dialog to select the project's root directory."""
        directory = filedialog.askdirectory()
        if directory:
            self.project_dir.set(directory)

    def update_file_content(self, file_path, pattern, replacement):
        """
        Reads a file, applies a regex substitution, and writes it back.
        Returns True if a change was made, False otherwise.
        """
        try:
            with open(file_path, 'r', encoding='utf-8') as file:
                content = file.read()
            
            # Use re.subn to get the number of substitutions made
            new_content, num_subs = re.subn(pattern, replacement, content, flags=re.IGNORECASE)

            if num_subs > 0:
                with open(file_path, 'w', encoding='utf-8') as file:
                    file.write(new_content)
                return True
        except FileNotFoundError:
            print(f"Warning: Could not find file {file_path}")
        except Exception as e:
            print(f"Error updating file {file_path}: {e}")
        return False

    def process_updates(self):
        """Main function to handle the update process when the button is clicked."""
        proj_dir = self.project_dir.get()
        if not proj_dir:
            messagebox.showerror("Error", "Please select the website project folder first.")
            return

        # A log to keep track of what was changed
        update_log = []

        # 1. Update DB Config (config.php)
        db_config_path = os.path.join(proj_dir, 'db_connection', 'config.php')
        if os.path.exists(db_config_path):
            if self.db_server.get():
                if self.update_file_content(db_config_path, r"define\('DB_SERVER', '.*?'\);", f"define('DB_SERVER', '{self.db_server.get()}');"):
                    update_log.append("Updated DB_SERVER.")
            if self.db_username.get():
                if self.update_file_content(db_config_path, r"define\('DB_USERNAME', '.*?'\);", f"define('DB_USERNAME', '{self.db_username.get()}');"):
                    update_log.append("Updated DB_USERNAME.")
            # We check for existence of password value, as empty is valid
            if self.update_file_content(db_config_path, r"define\('DB_PASSWORD', '.*?'\);", f"define('DB_PASSWORD', '{self.db_password.get()}');"):
                update_log.append("Updated DB_PASSWORD.")
            if self.db_name.get():
                if self.update_file_content(db_config_path, r"define\('DB_NAME', '.*?'\);", f"define('DB_NAME', '{self.db_name.get()}');"):
                    update_log.append("Updated DB_NAME.")
        else:
            update_log.append("Warning: db_connection/config.php not found.")
            
        # 2. Update API Key (generate_description.php)
        api_key_path = os.path.join(proj_dir, 'generate_description.php')
        if self.api_key.get():
            if os.path.exists(api_key_path):
                if self.update_file_content(api_key_path, r"\$api_key\s*=\s*'.*?';", f"$api_key = '{self.api_key.get()}';"):
                    update_log.append("Updated API key.")
            else:
                 update_log.append("Warning: generate_description.php not found.")

        # 3. Update <title> in all .php files
        if self.page_title.get():
            title_updated_count = 0
            for root_dir, _, files in os.walk(proj_dir):
                for filename in files:
                    if filename.endswith(".php"):
                        file_path = os.path.join(root_dir, filename)
                        if self.update_file_content(file_path, r"(<title>)(.*?)(</title>)", f"\\1{self.page_title.get()}\\3"):
                            title_updated_count += 1
            if title_updated_count > 0:
                update_log.append(f"Updated <title> tag in {title_updated_count} file(s).")
        
        # --- Final Report ---
        if not update_log:
            messagebox.showinfo("Finished", "No changes were made. Please fill in the fields for the values you want to change.")
        else:
            summary = "Update Complete!\n\nSummary:\n- " + "\n- ".join(update_log)
            messagebox.showinfo("Finished", summary)


if __name__ == "__main__":
    root = tk.Tk()
    app = ConfigEditorApp(root)
    root.mainloop()
